# -*- coding: utf-8 -*-
"""
1.18: ручная сборка OCMOD-теней availpro без Refresh.
Точная реплика механики admin/controller/marketplace/modification.php::refresh()
OpenCart 3.0.3.2: glob(GLOB_BRACE), CRLF->LF, построчный stripos-матчинг,
str_replace для replace, index/offset/trim, error=skip|break|abort,
запись только изменённых файлов (LF, без заголовков).

Запуск:  python .agents/analysis/1.18-apply-availpro.py [--write]
Без --write — только отчёт (dry-run).
"""
import glob as globmod
import io
import os
import re
import sys
import xml.etree.ElementTree as ET

ROOT = os.path.normpath(os.path.join(os.path.dirname(__file__), '..', '..'))
XML_PATH = os.path.join(ROOT, 'system', 'availpro.ocmod.xml')
MOD_DIR = os.path.join(ROOT, 'system', 'storage', 'modification')

WRITE = '--write' in sys.argv


def php_trim(s):
    # PHP trim() по умолчанию: " \t\n\r\0\x0B"
    return s.strip(" \t\n\r\0\x0B")


def expand_brace_glob(pattern):
    """glob с поддержкой {a,b} как GLOB_BRACE (один уровень вложенности достаточно)."""
    m = re.search(r'\{([^{}]*)\}', pattern)
    if not m:
        return globmod.glob(pattern)
    out = []
    for variant in m.group(1).split(','):
        out.extend(expand_brace_glob(pattern[:m.start()] + variant + pattern[m.end():]))
    return out


def apply_operation(content, search, add, position, offset, indexes, log):
    """Построчная реплика цикла применения операции. Возвращает (content, applied_count)."""
    lines = content.split('\n')
    i = 0  # счётчик матчинг-строк (как $i в PHP)
    line_id = 0
    applied = 0
    new_lines_add = add.split('\n')
    while line_id < len(lines):
        line = lines[line_id]
        match = False
        if search.lower() in line.lower():
            if not indexes:
                match = True
            elif i in indexes:
                match = True
            i += 1
        if match:
            if position == 'replace':
                lines[line_id:line_id + offset + 1] = [line.replace(search, add)]
                # PHP: при offset >= 0 $line_id не корректируется
            elif position == 'before':
                lines[line_id - offset:line_id - offset] = new_lines_add
                line_id += len(new_lines_add)
            else:  # after
                at = (line_id + 1) + offset
                lines[at:at] = new_lines_add
                line_id += len(new_lines_add)
            applied += 1
        line_id += 1
    return '\n'.join(lines), applied


def main():
    tree = ET.parse(XML_PATH)
    root = tree.getroot()

    modification = {}  # key -> текущий контент
    original = {}      # key -> исходный контент
    log = []
    written = []

    for file_el in root.findall('file'):
        path_attr = file_el.get('path', '')
        operations = file_el.findall('operation')
        for path_part in path_attr.split('|'):
            if path_part.startswith('catalog/'):
                full = os.path.join(ROOT, 'catalog', path_part[len('catalog/'):])
                prefix = 'catalog/'
            elif path_part.startswith('admin/'):
                full = os.path.join(ROOT, 'admin', path_part[len('admin/'):])
                prefix = 'admin/'
            elif path_part.startswith('system/'):
                full = os.path.join(ROOT, 'system', path_part[len('system/'):])
                prefix = 'system/'
            else:
                continue
            pattern = full.replace('\\', '/')
            for f in sorted(expand_brace_glob(pattern)):
                if not os.path.isfile(f):
                    continue
                rel = os.path.relpath(f, ROOT).replace('\\', '/')
                key = rel  # совпадает с ключом OCMOD (catalog/..., admin/...)
                if key not in modification:
                    with io.open(f, 'r', encoding='utf-8', errors='surrogateescape', newline='') as fh:
                        content = fh.read()
                    content = re.sub(r'\r?\n', '\n', content)
                    modification[key] = content
                    original[key] = content
                    log.append('FILE: ' + key)

                for op in operations:
                    error = op.get('error', '')
                    search_el = op.find('search')
                    add_el = op.find('add')
                    if search_el is None or add_el is None:
                        continue
                    regex = search_el.get('regex', '') == 'true'
                    if regex:
                        log.append('  REGEX-операция не поддерживается репликой: ' + key)
                        continue
                    search = search_el.text or ''
                    trim_attr = search_el.get('trim', '')
                    if trim_attr in ('', 'true'):
                        search = php_trim(search)
                    add = add_el.text or ''
                    add_trim = add_el.get('trim', '')
                    if add_trim == 'true':
                        add = php_trim(add)
                    position = add_el.get('position', '')
                    offset_s = add_el.get('offset', '')
                    offset = int(offset_s) if offset_s != '' else 0
                    index_s = search_el.get('index', '')
                    indexes = set(int(x) for x in index_s.split(',') if x.strip() != '') if index_s != '' else set()

                    new_content, applied = apply_operation(modification[key], search, add, position if position in ('replace', 'before', 'after') else 'replace', offset, indexes, log)
                    if applied:
                        modification[key] = new_content
                        log.append('  OK (%d x): %s' % (applied, search[:70]))
                    else:
                        if error == 'skip':
                            log.append('  NOT FOUND - OPERATION SKIPPED: ' + search[:70])
                            continue
                        elif error == 'abort':
                            log.append('  NOT FOUND - ABORT: ' + search[:70])
                            log.append('!! операция abort — реплика остановлена (в availpro отсутствует)')
                            for k in modification:
                                modification[k] = original[k]
                            break
                        else:
                            log.append('  NOT FOUND - OPERATIONS ABORTED (break файла): ' + search[:70])
                            break

    # запись только изменённых
    for key, value in modification.items():
        if original[key] != value:
            target = os.path.join(MOD_DIR, key.replace('/', os.sep))
            log.append('WRITE: ' + key)
            if WRITE:
                os.makedirs(os.path.dirname(target), exist_ok=True)
                with io.open(target, 'w', encoding='utf-8', errors='surrogateescape', newline='') as fh:
                    fh.write(value)
                written.append(key)
            else:
                written.append(key + ' (dry-run)')

    print('\n'.join(log))
    print('-' * 64)
    print('Файлов затронуто (изменено): %d из %d загруженных' % (len(written), len(modification)))
    for w in written:
        print('  ' + w)


if __name__ == '__main__':
    main()
