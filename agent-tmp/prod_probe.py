# -*- coding: utf-8 -*-
# Прод-проверка котировок ApiShip гостем (методика 1.25): только расчёт доставки,
# заказы не оформляются. Аргументы: product_id cart|door-прогоны фиксированы ниже.
import re
import sys
import urllib.parse
import urllib.request
import http.cookiejar

BASE = 'https://eridanus.ru/'
UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36'


def new_opener():
    cj = http.cookiejar.CookieJar()
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj)), cj


def get(op, url):
    req = urllib.request.Request(url, headers={'User-Agent': UA})
    with op.open(req, timeout=40) as r:
        return r.read().decode('utf-8', 'replace')


def post(op, url, fields):
    body = urllib.parse.urlencode(fields).encode('utf-8')
    req = urllib.request.Request(url, data=body,
                                 headers={'User-Agent': UA,
                                          'Content-Type': 'application/x-www-form-urlencoded'})
    with op.open(req, timeout=60) as r:
        return r.read().decode('utf-8', 'replace')


def product_id_from_page(html):
    m = re.search(r'product_id=(\d+)', html) or re.search(r"product_id\D+(\d+)", html)
    return m.group(1) if m else None


def labels(html):
    out = []
    for m in re.finditer(r'<label[^>]*>(.*?)</label>', html, re.S):
        lbl = re.sub(r'<[^>]+>', ' ', m.group(1))
        lbl = re.sub(r'\s+', ' ', lbl).strip()
        if any(k in lbl for k in ['5Post', 'Яндекс', 'Курьер', 'ПВЗ']) and len(lbl) < 220:
            out.append(lbl)
    return list(dict.fromkeys(out))


def run(name, product_id, payment, shipping_method=None):
    op, cj = new_opener()
    get(op, BASE)
    page = get(op, BASE + 'aw0022-01-sumochka-kakoo-zveryata-edinorog')  # прогрев сессии
    post(op, BASE + 'index.php?route=checkout/cart/add',
         {'product_id': product_id, 'quantity': 1})
    fields = {
        'customer[telephone]': '',
        'shipping_address[firstname]': '',
        'shipping_address[lastname]': '',
        'shipping_address[country_id]': 176,
        'shipping_address[zone_id]': 106,
        'shipping_address[city]': 'Санкт-Петербург',
        'payment_method': payment,
        'payment_method_current': payment,
        'comment': '',
        'next_step': 1,
        'simple_ajax': 1,
    }
    if shipping_method:
        fields['shipping_method'] = shipping_method
        fields['shipping_method_current'] = shipping_method
    resp = post(op, BASE + 'index.php?route=checkout/simplecheckout', fields)
    print('### %s (product_id=%s, оплата=%s, method=%s)' % (name, product_id, payment, shipping_method or '-'))
    for l in labels(resp):
        print('   ', l)
    if not labels(resp):
        print('    (методов не найдено, длина ответа %d)' % len(resp))
    return resp


if __name__ == '__main__':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    # product_id Поросёнка добудем со страницы
    op, _ = new_opener()
    pig_page = get(op, BASE + 'aw0022-11-sumochka-kakoo-zveryata-porosenok')
    pig_id = product_id_from_page(pig_page)
    print('Поросёнок product_id =', pig_id)

    run('A Единорог/карта/без method', '15382', 'modulbank')
    run('B Единорог/карта/method=5Post-error', '15382', 'modulbank', 'apiship.point_x5_439_error_2')
    run('C Единорог/нал/без method', '15382', 'cod')
    if pig_id:
        run('D Поросёнок/карта/без method', pig_id, 'modulbank')
