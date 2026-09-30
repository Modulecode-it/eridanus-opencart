# -*- coding: utf-8 -*-
# Грубый смоук-баланс фигурных скобок PHP-теней (вне строк/комментариев).
import glob
import io
import re

STR_SQ = re.compile(r"'(?:[^'\\]|\\.)*'")
STR_DQ = re.compile(r'"(?:[^"\\]|\\.)*"')
COMMENT_ML = re.compile(r'/\*.*?\*/', re.S)
COMMENT_SL = re.compile(r'//[^\n]*')

bad = []
files = glob.glob('system/storage/modification/**/*.php', recursive=True)
for f in files:
    src = io.open(f, encoding='utf-8', errors='replace').read()
    src = STR_SQ.sub("''", src)
    src = STR_DQ.sub('""', src)
    src = COMMENT_ML.sub('', src)
    src = COMMENT_SL.sub('', src)
    o, c = src.count('{'), src.count('}')
    if o != c:
        bad.append((f, o, c))

print('PHP-теней проверено:', len(files), '| рассинхрон скобок:', len(bad))
for b in bad:
    print('  !!', b)
