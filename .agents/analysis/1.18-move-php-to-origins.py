# -*- coding: utf-8 -*-
# 1.18: перенос PHP-патчей availpro из теней в оригиналы (контроллерная подмена
# на этой системе не работает: теневой loader.php не содержит modification()
# в методе controller()). twig-тени остаются в modification (шаблонная
# подмена работает через Template engine).
import io
import os

MOD_ROOT = 'system/storage/modification'

php_shadows = []
for base in ('catalog', 'admin'):
    for root, dirs, files in os.walk(os.path.join(MOD_ROOT, base)):
        for f in files:
            if f.endswith('.php'):
                php_shadows.append(os.path.join(root, f).replace(os.sep, '/'))

moved, removed = [], []
for s in php_shadows:
    content = io.open(s, encoding='utf-8', errors='surrogateescape').read()
    if 'avail' not in content.lower():
        continue  # чужие (сторонние моды) тени не трогаем
    orig = s[len(MOD_ROOT) + 1:]
    orig_content = io.open(orig, encoding='utf-8', errors='surrogateescape').read()
    norm = lambda t: t.replace('\r\n', '\n')
    if norm(orig_content) != norm(content):
        io.open(orig, 'w', encoding='utf-8', errors='surrogateescape', newline='').write(content)
        moved.append(orig)
    os.remove(s)
    removed.append(s)

print('Перенесено в оригиналы:', len(moved))
for m in sorted(moved):
    print('  ', m)
print('php-теней availpro удалено:', len(removed))
