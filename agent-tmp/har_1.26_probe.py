# -*- coding: utf-8 -*-
# Анализ HAR dev-сайта (1.26): карта ПВЗ не открывается, ApishipMap is not defined.
# Только чтение; HAR передаётся аргументом.
import json, sys, base64, re
from urllib.parse import urlparse

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

def main(path):
    with open(path, 'rb') as f:
        raw = f.read()
    text = raw.decode('utf-8-sig', 'replace')
    try:
        data = json.loads(text)
    except Exception as e:
        print('JSON ERROR:', e)
        return
    entries = data.get('log', {}).get('entries', [])
    print('ENTRIES:', len(entries))

    print('\n=== 1. Все запросы к dev-сайту (статус/путь) ===')
    site_bodies = []  # (idx, url, body)
    for i, e in enumerate(entries):
        url = e.get('request', {}).get('url', '')
        p = urlparse(url)
        if 'eridanus' not in p.netloc:
            continue
        status = (e.get('response') or {}).get('status')
        mime = ((e.get('response') or {}).get('content') or {}).get('mimeType', '')
        print(f'[{i}] {e.get("startedDateTime","")[11:19]} {e.get("request",{}).get("method")} {status} {p.path}?{p.query[:100]}')
        body = get_body_text((e.get('response') or {}).get('content', {}))
        if body:
            site_bodies.append((i, url, body))

    print('\n=== 2. Внешние скрипты карт (api-maps.yandex.ru и др.) ===')
    for i, e in enumerate(entries):
        url = e.get('request', {}).get('url', '')
        if 'yandex' in url or 'cdek' in url.lower() or 'apiship' in url.lower():
            status = (e.get('response') or {}).get('status')
            print(f'[{i}] {status} {url[:160]}')

    print('\n=== 3. Признаки в телах ответов dev-сайта ===')
    for i, url, body in site_bodies:
        hits = []
        for marker in ['apiship.js', 'ApishipMap', 'apiship_open', 'sdek.js', 'cdekymap',
                       'get_yandex_api_key', 'api-maps.yandex.ru', 'shipping_apiship_yandex_api_key']:
            c = body.count(marker)
            if c:
                hits.append(f'{marker}×{c}')
        if hits:
            print(f'[{i}] {urlparse(url).path}?{urlparse(url).query[:80]}')
            print('     ', ', '.join(hits))

    print('\n=== 4. Контекст подключения apiship.js в HTML (если есть) ===')
    for i, url, body in site_bodies:
        for m in re.finditer(r'.{120}apiship\.js.{160}', body):
            print(f'--- [{i}] {urlparse(url).path}')
            print(m.group(0).replace('\n', ' ')[:400])
            print()

    print('\n=== 5. Значение API-ключа в инлайне get_yandex_api_key ===')
    for i, url, body in site_bodies:
        m = re.search(r"function get_yandex_api_key\(\)\s*\{\s*return '([^']*)'", body)
        if m:
            print(f'[{i}] ключ в инлайне: {m.group(1)!r}')

    print('\n=== 6. Запросы get_points / set_point ===')
    for i, e in enumerate(entries):
        url = e.get('request', {}).get('url', '')
        if 'get_points' in url or 'set_point' in url:
            status = (e.get('response') or {}).get('status')
            body = get_body_text((e.get('response') or {}).get('content', {}))
            print(f'[{i}] {e.get("request",{}).get("method")} {status} {url[:140]}')
            pd = e.get('request', {}).get('postData', {})
            if pd:
                print('    POST:', str(pd.get('text', pd.get('params', '')))[:200])
            if body:
                print(f'    тело ответа: {len(body)} симв., начало: {body[:300]}')

if __name__ == '__main__':
    try:
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    except Exception:
        pass
    main(sys.argv[1])
