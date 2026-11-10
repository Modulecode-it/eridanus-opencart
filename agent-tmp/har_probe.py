# -*- coding: utf-8 -*-
# Парсер HAR-файлов заказчика (1.25, раунд 4). Только чтение, файлы передаются аргументами.
import json, sys, base64, re
from urllib.parse import urlparse, parse_qs, unquote_plus

def get_body_text(content):
    if not content:
        return None
    txt = content.get('text')
    if txt is None:
        return None
    if content.get('encoding') == 'base64':
        try:
            return base64.b64decode(txt).decode('utf-8', 'replace')
        except Exception:
            return None
    return txt

def post_fields(req):
    pd = req.get('postData')
    if not pd:
        return {}
    txt = pd.get('text', '')
    if not txt:
        for p in pd.get('params', []) or []:
            txt += ('&' if txt else '') + str(p.get('name','')) + '=' + str(p.get('value',''))
    try:
        return {k: v[0] for k, v in parse_qs(txt, keep_blank_values=True).items()}
    except Exception:
        return {'__raw__': txt[:500]}

INTERESTING_FIELDS = [
    'shipping_address_id','shipping_firstname','shipping_lastname','shipping_city',
    'shipping_zone_id','shipping_zone','shipping_country_id','shipping_address_1',
    'shipping_postcode','shipping_method','payment_method','shipping_code','payment_code',
    'address_id','firstname','lastname','city','zone_id','zone','address_1','postcode',
    'customer_email','email','shipping_apiship_point','apiship_point_id',
]

METHOD_MARKERS = ['5Post', '5post', 'Яндекс', 'yandex', 'ПВЗ', 'пвз', 'Курьер', 'курьер', 'apiship', 'Постамат']

def summarize_shipping_html(html):
    """Вытащить названия способов доставки и цены из HTML ответа чекаута."""
    out = []
    # радиокнопки способов доставки
    for m in re.finditer(r'<label[^>]*>(.*?)</label>', html, re.S):
        lbl = re.sub(r'<[^>]+>', ' ', m.group(1))
        lbl = re.sub(r'\s+', ' ', lbl).strip()
        if any(k in lbl for k in METHOD_MARKERS) and len(lbl) < 200:
            out.append('LABEL: ' + lbl)
    return out

def fix_escapes(s):
    """Посимвольно: валидные JSON-escape оставить, одиночные бэкслеши удвоить."""
    out = []
    i, n = 0, len(s)
    while i < n:
        c = s[i]
        if c == '\\':
            if i + 1 < n:
                nxt = s[i + 1]
                if nxt == 'u' and i + 5 < n and re.match(r'[0-9a-fA-F]{4}', s[i + 2:i + 6]):
                    out.append(s[i:i + 6]); i += 6; continue
                if nxt in '"\\/bfnrt':
                    out.append(s[i:i + 2]); i += 2; continue
            out.append('\\\\'); i += 1; continue
        out.append(c); i += 1
    return ''.join(out)


def repair_json(text):
    """Firefox HAR: неэкранированные кавычки в заголовках (filename="json.txt") + битые \\escape."""
    out_lines = []
    fixed = 0
    for line in text.splitlines(keepends=True):
        body = line.rstrip('\r\n')
        tail = line[len(body):]
        body = fix_escapes(body)
        # кавычки чиним только в коротких строках вида "key": "value" (заголовки),
        # длинные строки-контент не трогаем
        m = re.match(r'^(\s*"[A-Za-z0-9_\-]+":\s*")(.*)(",?)$', body)
        if m and len(body) < 500 and '"' in m.group(2):
            mid = m.group(2).replace('\\"', '"').replace('"', '\\"')
            body = m.group(1) + mid + m.group(3)
            fixed += 1
        out_lines.append(body + tail)
    return ''.join(out_lines), fixed

def main(path):
    print('=' * 100)
    print('FILE:', path)
    with open(path, 'rb') as f:
        raw = f.read()
    text = raw.decode('utf-8-sig', 'replace')
    try:
        data = json.loads(text)
    except Exception:
        text, fixed = repair_json(text)
        print(f'(JSON чинен: {fixed} строк)')
        try:
            data, idx = json.JSONDecoder().raw_decode(text)
            print(f'(хвост после JSON игнорируем: {len(text) - idx} симв.)')
        except Exception as e:
            print('JSON ERROR даже после ремонта:', e)
            return
    log = data.get('log', {})
    print('CREATOR:', log.get('creator'))
    pages = log.get('pages', [])
    if pages:
        print('PAGES:')
        for p in pages:
            print('   ', p.get('startedDateTime'), '|', p.get('title'))
    entries = log.get('entries', [])
    print('ENTRIES:', len(entries))

    checkout_posts = 0
    for i, e in enumerate(entries):
        req = e.get('request', {})
        url = req.get('url', '')
        parsed = urlparse(url)
        method = req.get('method')
        status = (e.get('response') or {}).get('status')
        is_site = 'eridanus.ru' in parsed.netloc
        is_checkout = is_site and 'checkout' in parsed.query
        # печатаем компактную строку для каждого запроса к сайту
        if is_site:
            print(f'[{i}] {e.get("startedDateTime","")[11:19]} {method} {status} {parsed.path}?{parsed.query[:160]}')
        if is_checkout and method == 'GET':
            body = get_body_text((e.get('response') or {}).get('content', {}))
            if body:
                print(f'    === GET-ответ: методы доставки при первой отрисовке:')
                for s in list(dict.fromkeys(summarize_shipping_html(body), []))[:12]:
                    print('        ', s)
                # состав корзины: фрагменты с названиями товаров (simplecheckout_cart)
                for m in re.finditer(r'(?:title|alt)="([^"]{8,80})"', body):
                    t = m.group(1)
                    if any(ch.isalpha() for ch in t) and not t.startswith(('data:','http')):
                        pass
                cart_zone = re.search(r'simplecheckout_cart.{0,4000}', body, re.S)
                if cart_zone:
                    names = re.findall(r'>([^<>]{6,80})</a>', cart_zone.group(0))
                    names = [n.strip() for n in names if any(c.isalpha() for c in n)]
                    print('    === корзина (ссылки в блоке):', names[:12])
        if is_checkout and method == 'POST':
            checkout_posts += 1
            flds = post_fields(req)
            sel = {k: v for k, v in flds.items() if k in INTERESTING_FIELDS and v}
            print(f'    >>> POST #{checkout_posts} поля адреса/методов:')
            for k, v in sel.items():
                print(f'        {k} = {v!r}')
            # если есть нестандартные ключи с city/zone/apiship
            extra = {k: v for k, v in flds.items() if ('city' in k.lower() or 'zone' in k.lower() or 'apiship' in k.lower() or 'point' in k.lower()) and k not in sel}
            for k, v in extra.items():
                print(f'        + {k} = {v!r}')
            # ответ
            content = (e.get('response') or {}).get('content', {})
            body = get_body_text(content)
            if body:
                ships = summarize_shipping_html(body)
                if ships:
                    dedup = list(dict.fromkeys(ships))
                    print(f'    <<< ОТВЕТ ({len(body)} симв.), способы доставки:')
                    for s in dedup[:25]:
                        print('        ', s)
                for marker, tag in [('Яндекс', 'YANDEX'), ('5Post', '5POST'), ('apiship', 'APISHIP')]:
                    cnt = body.count(marker)
                    if cnt:
                        print(f'        вхождений {marker!r}: {cnt}')
                # ошибки Simple
                for m in re.finditer(r'(error[^"<>]{0,60}|warning[^"<>]{0,60})', body[:200000], re.I):
                    pass
                errs = re.findall(r'class="[^"]*(simplecheckout-error|error)[^"]*"[^>]*>([^<]{0,120})', body)
                for cls, txt in errs[:10]:
                    if txt.strip():
                        print(f'        ERR? [{cls}] {txt.strip()!r}')
            else:
                print('    <<< ответ без тела (content.text отсутствует)')
    # некорневая информация: логин-признаки
    for i, e in enumerate(entries):
        url = e.get('request', {}).get('url', '')
        if 'eridanus.ru' not in url:
            continue
        body = get_body_text((e.get('response') or {}).get('content', {})) or ''
        if not body:
            continue
        if 'Выберите адрес' in body or 'choose address' in body.lower():
            print(f'[{i}] ЛОГИН-признак: в ответе есть «Выберите адрес» (адресная книга) — авторизован')
            break

if __name__ == '__main__':
    try:
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    except Exception:
        pass
    for p in sys.argv[1:]:
        try:
            main(p)
        except Exception as ex:
            print('FAIL', p, repr(ex))
