# Справочник graphify: инкрементальное обновление и cluster-only

Загружай этот файл, только если пользователь передал `--update` или `--cluster-only`. При первичной полной сборке этот файл никогда не читается.

## Для --update (инкрементальное переизвлечение)

Используй, если с прошлого запуска ты добавил или изменил файлы. Переизвлекает только изменённые файлы — экономит токены и время.

```bash
$(cat graphify-out/.graphify_python) -c "
import sys, json
from graphify.detect import detect_incremental, save_manifest
from pathlib import Path

result = detect_incremental(Path('INPUT_PATH'))
new_total = result.get('new_total', 0)
print(json.dumps(result, indent=2, ensure_ascii=False))
Path('graphify-out/.graphify_incremental.json').write_text(json.dumps(result, ensure_ascii=False), encoding=\"utf-8\")
deleted = list(result.get('deleted_files', []))
if new_total == 0 and not deleted:
    print('No files changed since last run. Nothing to update.')
    raise SystemExit(0)
if deleted:
    print(f'{len(deleted)} deleted file(s) to prune.')
if new_total > 0:
    print(f'{new_total} new/changed file(s) to re-extract.')
"
```

Затем заполни `.graphify_detect.json`, чтобы Шаги 3A–6 (которые читают его безусловно) видели корректное состояние для инкрементального запуска. `files` несёт изменённое подмножество (управляет AST Шага 3A и проверкой кэша Шага 3B0 только по изменённым файлам); `all_files` несёт весь корпус для любого шага, которому нужен контекст всего корпуса:

```bash
$(cat graphify-out/.graphify_python) -c "
import json
from pathlib import Path
r = json.loads(Path('graphify-out/.graphify_incremental.json').read_text(encoding=\"utf-8\"))
Path('graphify-out/.graphify_detect.json').write_text(json.dumps({
    'files': r.get('new_files', {}),
    'all_files': r.get('files', {}),
    'total_files': r.get('new_total', 0),
    'total_words': r.get('total_words', 0),
    'skipped_sensitive': r.get('skipped_sensitive', []),
    'needs_graph': True,
}, ensure_ascii=False), encoding=\"utf-8\")
"
```

Если новые файлы есть, сначала проверь, все ли изменённые файлы являются файлами кода:

```bash
$(cat graphify-out/.graphify_python) -c "
import json
from pathlib import Path

result = json.loads(open('graphify-out/.graphify_incremental.json', encoding='utf-8').read()) if Path('graphify-out/.graphify_incremental.json').exists() else {}
code_exts = {'.py','.ts','.js','.go','.rs','.java','.cpp','.c','.rb','.swift','.kt','.cs','.scala','.php','.cc','.cxx','.hpp','.h','.kts','.lua','.toc','.f','.F','.f90','.F90','.f95','.F95','.f03','.F03','.f08','.F08'}
new_files = result.get('new_files', {})
all_changed = [f for files in new_files.values() for f in files]
code_only = all(Path(f).suffix.lower() in code_exts for f in all_changed)
print('code_only:', code_only)
"
```

Если `code_only` равно True: выведи `[graphify update] Code-only changes detected - skipping semantic extraction (no LLM needed)`, выполни только Шаг 3A (AST) на изменённых файлах, полностью пропусти Шаг 3B (без сабагентов), затем переходи сразу к merge и Шагам 4–8.

Если `code_only` равно False (хотя бы один изменённый файл — документ/статья/изображение/видео): **сначала, если какой-то изменённый файл есть в `new_files['video']`, выполни `references/transcribe.md` (Шаг 2.5) на этих файлах, затем перепиши `.graphify_detect.json`, переместив полученные пути транскриптов в `files['document']` и удалив `files['video']`** — иначе сырые пути `.mp4/.mp3` попадут в семантические сабагенты как нечитаемые медиа (#1392). Затем выполни полный пайплайн Шагов 3A–3C как обычно.


Если новых файлов нет (только удаления), создай пустую экстракцию, чтобы шаг merge мог выполнить чистку:

```bash
if [ ! -f graphify-out/.graphify_extract.json ]; then
    echo '[graphify update] Only deletions -- creating empty extraction for merge.'
    $(cat graphify-out/.graphify_python) -c "
import json
from pathlib import Path
Path('graphify-out/.graphify_extract.json').write_text(json.dumps({'nodes':[],'edges':[],'hyperedges':[],'input_tokens':0,'output_tokens':0}), encoding='utf-8')
"
fi
```


Затем:

```bash
$(cat graphify-out/.graphify_python) -c "
import json
from pathlib import Path
from graphify.build import build_merge
from graphify.detect import save_manifest

# Загрузить новую экстракцию и инкрементальное состояние
new_extraction = json.loads(Path('graphify-out/.graphify_extract.json').read_text(encoding=\"utf-8\"))
incremental = json.loads(Path('graphify-out/.graphify_incremental.json').read_text(encoding=\"utf-8\"))
deleted = list(incremental.get('deleted_files', []))
# prune_sources — ТОЛЬКО для действительно УДАЛЁННЫХ файлов. Изменённые/переизвлечённые
# файлы обрабатывает replace-on-re-extract в build_merge (#1344): каждый source_file из
# new_chunks удаляется из базы перед merge, поэтому старые/устаревшие узлы не выживают.
# НЕ добавляй `changed` сюда: при переданном root= prune_set относит пути к той же базе,
# что и только что слитые узлы, и УДАЛИЛ бы переизвлечённый контент (#1178 неактуален,
# поскольку изменённые файлы согласовывает replace, а не проход дедупликации).
prune = list(deleted) or None

# Используй build_merge() — читает graph.json напрямую, без конвертации туда-обратно через
# NetworkX, поэтому направление рёбер (calls, implements, imports) всегда сохраняется (#801).
# Передай root=, чтобы prune_sources (абсолютные пути из detect_incremental) стали
# относительными и совпали с относительными source_file графа; без этого ничего не
# вычищается, и устаревшие узлы накапливаются при каждом обновлении (#1361).
# directed=IS_DIRECTED: замени IS_DIRECTED на True, если передан --directed, иначе False.
# Без этого --directed --update молча пересобирает ненаправленный граф и схлопывает
# взаимные рёбра A<->B (#1392).
G = build_merge(
    [new_extraction],
    graph_path='graphify-out/graph.json',
    prune_sources=prune,
    root='INPUT_PATH',
    directed=IS_DIRECTED,
)
print(f'[graphify update] Merged: {G.number_of_nodes()} nodes, {G.number_of_edges()} edges')

# Записать результат merge обратно в .graphify_extract.json, чтобы Шаг 4 видел весь граф
merged_out = {
    'nodes': [{'id': n, **d} for n, d in G.nodes(data=True)],
    'edges': [
        # source/target явно последними, чтобы они перебивали любые устаревшие атрибуты в d.
        {**{k: val for k, val in d.items() if k not in ('_src', '_tgt', 'source', 'target')},
         'source': d.get('_src', u), 'target': d.get('_tgt', v)}
        for u, v, d in G.edges(data=True)
    ],
    # G.graph["hyperedges"] содержит гиперрёбра как из существующего graph.json,
    # так и из new_extraction (build_merge объединяет их). Fallback только к
    # new_extraction молча отбросил бы гиперрёбра прошлого запуска (#801).
    'hyperedges': list(G.graph.get('hyperedges', [])),
    'input_tokens': new_extraction.get('input_tokens', 0),
    'output_tokens': new_extraction.get('output_tokens', 0),
}
Path('graphify-out/.graphify_extract.json').write_text(json.dumps(merged_out, ensure_ascii=False), encoding=\"utf-8\")
print(f'[graphify update] Merged extraction written ({len(merged_out[\"nodes\"])} nodes, {len(merged_out[\"edges\"])} edges)')

# Сохрани манифест, чтобы следующий --update сравнивал с сегодняшним состоянием, а не
# с базой прошлого запуска (предотвращает отчёты о ghost-узлах при последующих обновлениях).
save_manifest(incremental['files'])
print('[graphify update] Manifest saved.')
"
```

Затем выполни Шаги 4–8 над слитым графом как обычно.

После Шага 4 покажи diff графа:

```bash
$(cat graphify-out/.graphify_python) -c "
import json
from graphify.analyze import graph_diff
from graphify.build import build_from_json
from networkx.readwrite import json_graph
import networkx as nx
from pathlib import Path

# Загрузить старый граф (до обновления) из бэкапа, записанного перед merge
old_data = json.loads(Path('graphify-out/.graphify_old.json').read_text(encoding=\"utf-8\")) if Path('graphify-out/.graphify_old.json').exists() else None
new_extract = json.loads(Path('graphify-out/.graphify_extract.json').read_text(encoding=\"utf-8\"))
G_new = build_from_json(new_extract, directed=IS_DIRECTED)

if old_data:
    G_old = json_graph.node_link_graph(old_data, edges='links')
    diff = graph_diff(G_old, G_new)
    print(diff['summary'])
    if diff['new_nodes']:
        print('New nodes:', ', '.join(n['label'] for n in diff['new_nodes'][:5]))
    if diff['new_edges']:
        print('New edges:', len(diff['new_edges']))
"
```

Перед шагом merge сохрани старый граф: `cp graphify-out/graph.json graphify-out/.graphify_old.json`
После — очисти: `rm -f graphify-out/.graphify_old.json`

---

## Для --cluster-only

Пропусти Шаги 1–3. Перезапусти кластеризацию на существующем графе:

```bash
graphify cluster-only .
```

Команда `graphify cluster-only .` **самодостаточна**: она перекластеризует граф, называет сообщества и регенерирует `GRAPH_REPORT.md`, `graph.json` и `graph.html` из существующего графа. **НЕ перезапускай Шаги 5–9** — они читают промежуточные файлы (`.graphify_extract.json`, `.graphify_detect.json`, `.graphify_analysis.json`), которые очистка предыдущей сборки (Шаг 9) уже удалила, поэтому они выбросят `FileNotFoundError` (#1392). Когда команда завершится, представь обновлённое резюме `GRAPH_REPORT.md` как обычно.
