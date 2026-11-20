# -*- coding: utf-8 -*-
"""Проба калькулятора ApiShip: как region/street в addressString влияют на pointIds.
Задача 1.28-доп: «мало точек ПВЗ при сохранённом адресе». Токен — dev (в чат/файлы не коммитить)."""
import json, urllib.request

TOKEN = "<dev-токен из настроек модуля ApiShip на dev; сюда не вставлять>"
URL = "https://api.apiship.ru/v1/calculator"

FROM = {"countryCode": "RU", "addressString": "Санкт-Петербург ,  Санкт-Петербург,  Планерная 47"}
PLACES = [{"height": 3, "length": 19, "width": 15, "weight": 300}]

def build_addr(region, city, street):
    # повторяет get_address() модуля при пустых regionType/cityType/streetType
    address = (region or "") + " "
    if (region or "") == "г":  # не наш случай
        pass
    address = address + ",  " + (city or "")
    if street:
        address = address + ",  " + street
    return address

VARIANTS = [
    ("region+city+street (сохр. адрес)", build_addr("Санкт-Петербург", "Санкт-Петербург", "Тестовая ул., д. 1 (TEST)")),
    ("region+city, без улицы", build_addr("Санкт-Петербург", "Санкт-Петербург", "")),
    ("только city (новый адрес)", build_addr("", "Санкт-Петербург", "")),
    ("city+street, без region", build_addr("", "Санкт-Петербург", "Тестовая ул., д. 1 (TEST)")),
]

def calc(address_string):
    body = {"from": FROM, "to": {"countryCode": "RU", "addressString": address_string},
            "places": PLACES, "includeFees": 1}
    req = urllib.request.Request(URL, data=json.dumps(body).encode("utf-8"),
                                 headers={"Content-Type": "application/json",
                                          "Accept": "application/json",
                                          "Authorization": TOKEN})
    try:
        with urllib.request.urlopen(req, timeout=30) as r:
            return json.loads(r.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        raise RuntimeError("HTTP %s: %s" % (e.code, e.read().decode("utf-8", "replace")[:800]))

import io
out = io.open("agent-tmp/probe_out.json", "w", encoding="utf-8")
for name, addr in VARIANTS:
    rec = {"variant": name, "addressString": addr}
    try:
        j = calc(addr)
    except Exception as e:
        rec["error"] = str(e)[:800]
        out.write(json.dumps(rec, ensure_ascii=False) + "\n"); continue
    dtp = j.get("deliveryToPoint") or []
    dtd = j.get("deliveryToDoor") or []
    rec["deliveryToDoor"] = [p.get("providerKey") for p in dtd]
    rec["points"] = []
    for p in dtp:
        pids = set()
        for t in (p.get("tariffs") or []):
            for pid in (t.get("pointIds") or []):
                pids.add(pid)
        rec["points"].append({"provider": p.get("providerKey"), "tariffs": len(p.get("tariffs") or []), "pointIds": len(pids)})
    out.write(json.dumps(rec, ensure_ascii=False) + "\n")
out.close()
print("done")
