# -*- coding: utf-8 -*-
# Смоук-баланс скобок изменённых оригинальных PHP (после переноса патчей из теней).
import io
import re
import subprocess

STR_SQ = re.compile(r"'(?:[^'\\]|\\.)*'")
STR_DQ = re.compile(r'"(?:[^"\\]|\\.)*"')
CM = re.compile(r'/\*.*?\*/', re.S)
CS = re.compile(r'//[^\n]*')

files = subprocess.run(['git', 'diff', '--name-only'], capture_output=True, text=True).stdout.split()
import os
php = [f for f in files if f.endswith('.php') and os.path.isfile(f)]
bad = []
for f in php:
    src = io.open(f, encoding='utf-8', errors='replace').read()
    src = STR_SQ.sub("''", src)
    src = STR_DQ.sub('""', src)
    src = CM.sub('', src)
    src = CS.sub('', src)
    if src.count('{') != src.count('}'):
        bad.append((f, src.count('{'), src.count('}')))
print('изменённых php проверено:', len(php), '| проблем:', len(bad))
for b in bad:
    print('  !!', b)
