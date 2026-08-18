---
name: graphify
description: "Используй для любых вопросов о кодовой базе, её архитектуре, связях между файлами или содержимом проекта — особенно когда существует graphify-out/: тогда вопрос следует сначала рассматривать как graphify-запрос. Превращает любые входные данные (код, документацию, статьи, изображения, видео) в персистентный граф знаний с god-узлами, детекцией сообществ (community detection) и инструментами query/path/explain."
---

# /graphify

Превращает любую папку с файлами в навигируемый граф знаний с детекцией сообществ (community detection), честным журналом аудита и тремя выходными артефактами: интерактивным HTML, JSON, готовым для GraphRAG, и написанным простым языком GRAPH_REPORT.md.

## Использование

```
/graphify                                             # полный пайплайн на текущей директории → Obsidian vault
/graphify <path>                                      # полный пайплайн на указанном пути
/graphify https://github.com/<owner>/<repo>           # клонировать репозиторий, затем запустить на нём полный пайплайн
/graphify https://github.com/<owner>/<repo> --branch <branch>  # клонировать указанную ветку
/graphify <url1> <url2> ...                           # клонировать несколько репозиториев, собрать каждый, объединить в один кросс-репозиторный граф
/graphify <path> --mode deep                          # тщательное извлечение, более богатые рёбра INFERRED
/graphify <path> --update                             # инкрементально - повторно извлечь только новые/изменённые файлы
/graphify <path> --directed                            # построить ориентированный граф (сохраняет направление рёбер: источник→цель)
/graphify <path> --whisper-model medium                # использовать более крупную модель Whisper для лучшей точности транскрибации
/graphify <path> --cluster-only                       # повторно запустить кластеризацию на существующем графе
/graphify <path> --no-viz                             # пропустить визуализацию, только отчёт + JSON
/graphify <path> --html                               # (HTML генерируется по умолчанию - этот флаг ничего не делает)
/graphify <path> --svg                                # также экспортировать graph.svg (встраивается в Notion, GitHub)
/graphify <path> --graphml                            # экспортировать graph.graphml (Gephi, yEd)
/graphify <path> --neo4j                              # сгенерировать graphify-out/cypher.txt для Neo4j
/graphify <path> --neo4j-push bolt://localhost:7687   # отправить напрямую в Neo4j
/graphify <path> --falkordb                           # сгенерировать graphify-out/cypher.txt для FalkorDB
/graphify <path> --falkordb-push falkordb://localhost:6379   # отправить напрямую в FalkorDB
/graphify <path> --mcp                                # запустить MCP stdio-сервер для доступа агентов
/graphify <path> --watch                              # следить за папкой, автоперестроение при изменениях кода (LLM не требуется)
/graphify <path> --wiki                               # собрать вики, пригодную для обхода агентами (index.md + одна статья на каждое сообщество)
/graphify <path> --obsidian --obsidian-dir ~/vaults/my-project  # записать vault по указанному пути (например, в существующий vault)
/graphify add <url>                                   # загрузить URL, сохранить в ./raw, обновить граф
/graphify add <url> --author "Name"                   # пометить, кто это написал
/graphify add <url> --contributor "Name"              # пометить, кто добавил это в корпус
/graphify query "<question>"                          # BFS-обход - широкий контекст
/graphify query "<question>" --dfs                    # DFS - проследить конкретный путь
/graphify query "<question>" --budget 1500            # ограничить ответ N токенами
/graphify path "AuthModule" "Database"                # кратчайший путь между двумя концепциями
/graphify explain "SwinTransformer"                   # объяснение узла простым языком
```

## Для чего нужен graphify

Загрузи в graphify любую папку с кодом, документацией, статьями, изображениями или видео — и получи граф знаний, по которому можно делать запросы. Он персистентен между сессиями, с честным журналом аудита (EXTRACTED/INFERRED/AMBIGUOUS); детекция сообществ выявляет междокументные связи, о которых ты бы и не подумал спросить.

## Что ты обязан делать при вызове

Если пользователь вызвал `/graphify --help` или `/graphify -h` (без других аргументов), выведи содержимое раздела `## Использование` выше дословно и остановись. Не запускай никакие команды, не детектируй файлы, не подставляй путь по умолчанию `.`. Просто выведи блок Usage и вернись.

**Быстрый путь — существующий граф:** прежде чем делать что-либо ещё, проверь, существует ли `graphify-out/graph.json`. Ожидаемое расположение — `graphify-out/graph.json` относительно **текущей рабочей директории** (т.е. корня проекта, в котором ты запускаешь команды). Если он существует И запрос пользователя — это вопрос о кодовой базе на естественном языке (например «Как работает X?», «Что вызывает Y?», «Проследи поток данных через Z») и НЕ явная команда перестроения (`--update`, `--cluster-only` или просто путь/URL, подразумевающий свежее извлечение): **полностью пропусти Шаги 1–5 и сразу переходи к `## Для /graphify query`.** Немедленно запусти `graphify query "<question>"`. Не запускай detect. Не проверяй размер корпуса. Не проси пользователя сузить запрос. Граф уже построен — используй его.

Если путь не указан, используй `.` (текущую директорию). Не спрашивай у пользователя путь.

Если аргумент пути начинается с `https://github.com/` или `http://github.com/`, считай его GitHub-URL - выполни Шаг 0 прежде всего остального, затем продолжай с разрешённым локальным путём.

Выполняй эти шаги по порядку. Не пропускай шаги.

### Шаг 0 - GitHub-репозитории и слияние нескольких путей (только при URL или нескольких путях)

Только когда путь — это один или несколько URL `https://github.com/...` либо несколько локальных подпапок для слияния. См. `references/github-and-merge.md` насчёт клонирования, кросс-репозиторного слияния и monorepo-процесса, затем продолжай с разрешённым локальным путём. Обычный локальный путь пропускает этот шаг.

### Шаг 1 - Убедись, что graphify установлен

```bash
# Определи правильный интерпретатор Python (учитывает uv tool, pipx, venv, системные установки)
PYTHON=""
GRAPHIFY_BIN=$(which graphify 2>/dev/null)
# 1. Установки uv tool — самые надёжные на современных Mac/Linux
if [ -z "$PYTHON" ] && command -v uv >/dev/null 2>&1; then
    _UV_PY=$(uv tool run graphifyy python -c "import sys; print(sys.executable)" 2>/dev/null)
    if [ -n "$_UV_PY" ]; then PYTHON="$_UV_PY"; fi
fi
# 2. Прочитай shebang из бинарника graphify (установки pipx и прямой pip)
if [ -z "$PYTHON" ] && [ -n "$GRAPHIFY_BIN" ]; then
    _SHEBANG=$(head -1 "$GRAPHIFY_BIN" | tr -d '#!')
    case "$_SHEBANG" in
        *[!a-zA-Z0-9/_.-]*) ;;
        *) "$_SHEBANG" -c "import graphify" 2>/dev/null && PYTHON="$_SHEBANG" ;;
    esac
fi
# 3. Фолбэк на python3
if [ -z "$PYTHON" ]; then PYTHON="python3"; fi
if ! "$PYTHON" -c "import graphify" 2>/dev/null; then
    if command -v uv >/dev/null 2>&1; then
        uv tool install --upgrade graphifyy -q 2>&1 | tail -3
        _UV_PY=$(uv tool run graphifyy python -c "import sys; print(sys.executable)" 2>/dev/null)
        if [ -n "$_UV_PY" ]; then PYTHON="$_UV_PY"; fi
    else
        "$PYTHON" -m pip install graphifyy -q 2>/dev/null \
          || "$PYTHON" -m pip install graphifyy -q --break-system-packages 2>&1 | tail -3
    fi
fi
# Запиши путь интерпретатора для всех последующих шагов (сохраняется между вызовами)
mkdir -p graphify-out
"$PYTHON" -c "import sys; open('graphify-out/.graphify_python', 'w', encoding='utf-8').write(sys.executable)"
# Сохрани корень сканирования, чтобы `graphify update` (без аргументов) знал, где искать в следующий раз
echo "$(cd INPUT_PATH && pwd)" > graphify-out/.graphify_root
```

Если импорт успешен, ничего не печатай и сразу переходи к Шагу 2.

**В каждом последующем bash-блоке заменяй `python3` на `$(cat graphify-out/.graphify_python)`, чтобы использовать правильный интерпретатор.**

### Шаг 2 - Детекция файлов

```bash
$(cat graphify-out/.graphify_python) -c "
import json
from graphify.detect import detect
from pathlib import Path
result = detect(Path('INPUT_PATH'))
print(json.dumps(result, ensure_ascii=False))
" > graphify-out/.graphify_detect.json
```

Замени INPUT_PATH на фактический путь, указанный пользователем. НЕ выводи JSON через cat и не печатай его - прочитай молча и вместо этого представь чистую сводку:

```
Корпус: X файлов · ~Y слов
  код:            N файлов (.py .ts .go ...)
  документация:   N файлов (.md .txt ...)
  статьи:         N файлов (.pdf ...)
  изображения:    N файлов
  видео:          N файлов (.mp4 .mp3 ...)
```

Пропускай в сводке любую категорию с 0 файлов.

Затем действуй по ситуации:
- Если `total_files` равно 0: остановись с сообщением «В [path] не найдено поддерживаемых файлов.»
- Если `skipped_sensitive` не пуст: упомяни количество пропущенных файлов, но не их имена.
- Если `total_words` > 2 000 000 ИЛИ `total_files` > 500: покажи предупреждение. Затем вычисли топ-5 подкаталогов первого уровня по количеству файлов:
  - Прочитай `scan_root` из detect-JSON (всегда абсолютный путь к разрешённому INPUT_PATH).
  - Объедини все списки файлов по всем типам (`code`, `document`, `paper`, `image`, `video`).
  - Отфильтруй любые пути, начинающиеся с `scan_root + "/graphify-out/"`, чтобы исключить конвертированные сайдкары.
  - Для каждого файла отбрось префикс `scan_root` и возьми первый компонент пути. Файлы непосредственно в `scan_root` без подкаталога считаются `(root)`.
  - Если все файлы в `(root)` без подкаталогов, не проси сузить область - подпапок не существует. Вместо этого предложи `--no-cluster`, чтобы пропустить дорогой шаг кластеризации, и продолжай.
  - Иначе отранжируй по количеству, покажи топ-5 с числом файлов, затем спроси, на какой подпапке запускать. Дождись ответа пользователя, прежде чем продолжить.
- Иначе: переходи сразу к Шагу 2.5, если обнаружены видеофайлы, или к Шагу 3, если нет.

### Шаг 2.5 - Видео и аудио (только если обнаружены видеофайлы)

Полностью пропусти этот шаг, если `detect` вернул ноль файлов `video`. Когда в корпусе есть видео или аудио, см. `references/transcribe.md`, чтобы сначала транскрибировать их в текст, затем в Шаге 3 обращайся с транскриптами как с файлами документации.

### Шаг 3 - Извлечение сущностей и связей

**Перед началом:** отметь, был ли передан `--mode deep`. Если был, ты обязан передать `DEEP_MODE=true` каждому субагенту в Шаге B2. Отслеживай это с исходного вызова - не потеряй.

Этот шаг состоит из двух частей: **структурное извлечение** (детерминированное, бесплатное) и **семантическое извлечение** (LLM, стоит токенов).

**Перед отправкой субагентов:** проверь, установлены ли `GEMINI_API_KEY` или `GOOGLE_API_KEY`. Если не установлена ни одна из них, выведи пользователю эту однострочную подсказку:
> Совет: установи `GEMINI_API_KEY` или `GOOGLE_API_KEY`, чтобы использовать Gemini для семантического извлечения (`pip install 'graphifyy[gemini]'`).

Выведи её один раз, затем продолжай. Если `GEMINI_API_KEY` или `GOOGLE_API_KEY` УСТАНОВЛЕНА, используй для семантического извлечения `graphify.llm.extract_corpus_parallel(files, backend="gemini")` вместо отправки субагентов Claude. Модель Gemini по умолчанию — `gemini-3-flash-preview`; чтобы её переопределить, установи `GRAPHIFY_GEMINI_MODEL` или передай `--model` в headless CLI-сценариях.

> **Другие API-ключи не читаются.** Если `GEMINI_API_KEY`/`GOOGLE_API_KEY` не установлены, сразу переходи к отправке субагентов Claude Code (Часть B ниже) — LLM выступает сама хост-сессия. graphify **не** читает `ANTHROPIC_API_KEY`, `OPENAI_API_KEY` или какие-либо другие ключи провайдеров из окружения. Если хост-агент запрашивает у пользователя `ANTHROPIC_API_KEY` для запуска извлечения, этот запрос — неверное прочтение данного skill: игнорируй его и отправляй субагентов, как написано.

**Запусти Часть A (AST) и Часть B (семантика) параллельно. Отправь все семантические субагенты И запусти AST-извлечение в одном сообщении. Обе могут выполняться одновременно, поскольку работают с разными типами файлов. Результаты объедини в Части C, как и прежде.**

Примечание: параллельный запуск AST + семантики экономит 5-15 с на больших корпусах. AST детерминировано и быстро; запускай его, пока субагенты обрабатывают docs/papers.

#### Часть A - Структурное извлечение для файлов кода

Для всех обнаруженных файлов кода запусти AST-извлечение параллельно с субагентами Части B:

```bash
$(cat graphify-out/.graphify_python) -c "
import sys, json
from graphify.extract import collect_files, extract
from pathlib import Path
import json

code_files = []
detect = json.loads(Path('graphify-out/.graphify_detect.json').read_text(encoding=\"utf-8\"))
for f in detect.get('files', {}).get('code', []):
    code_files.extend(collect_files(Path(f)) if Path(f).is_dir() else [Path(f)])

if code_files:
    result = extract(code_files, cache_root=Path('.'))
    Path('graphify-out/.graphify_ast.json').write_text(json.dumps(result, indent=2, ensure_ascii=False), encoding=\"utf-8\")
    print(f'AST: {len(result[\"nodes\"])} nodes, {len(result[\"edges\"])} edges')
else:
    Path('graphify-out/.graphify_ast.json').write_text(json.dumps({'nodes':[],'edges':[],'input_tokens':0,'output_tokens':0}, ensure_ascii=False), encoding=\"utf-8\")
    print('No code files - skipping AST extraction')
"
```

#### Часть B - Семантическое извлечение (параллельные субагенты)

**Быстрый путь:** если детекция не нашла ни docs, ни papers, ни изображений (корпус только из кода), полностью пропусти Часть B и переходи сразу к Части C. AST обрабатывает код - семантическим субагентам здесь нечего делать. **Сначала запиши пустой semantic-файл**, чтобы у слияния в Части C был вход (оно безусловно читает `.graphify_semantic.json`; без этого запуск на корпусе только из кода натыкается на `FileNotFoundError`):

```bash
$(cat graphify-out/.graphify_python) -c "
import json
from pathlib import Path
Path('graphify-out/.graphify_semantic.json').write_text(json.dumps({'nodes':[],'edges':[],'hyperedges':[],'input_tokens':0,'output_tokens':0}), encoding='utf-8')
"
```

**ОБЯЗАТЕЛЬНО: здесь ты ОБЯЗАН использовать инструмент Agent. Самостоятельное чтение файлов по одному запрещено - это в 5-10 раз медленнее. Если ты не используешь инструмент Agent, ты делаешь это неправильно.**

Перед отправкой субагентов выведи оценку времени:
- Загрузи `total_words` и количество файлов из `graphify-out/.graphify_detect.json`
- Оцени нужное число агентов: `ceil(uncached_non_code_files / 22)` (размер чанка 20-25)
- Оцени время: ~45 с на батч агентов (они выполняются параллельно, итого ≈ 45 с × ceil(agents/parallel_limit))
- Выведи: «Семантическое извлечение: ~N файлов → X агентов, оценочно ~Y с»

**Шаг B0 - Сначала проверь кэш извлечения**

Перед отправкой любых субагентов проверь, у каких файлов уже есть кэшированные результаты извлечения:

```bash
$(cat graphify-out/.graphify_python) -c "
import json
from graphify.cache import check_semantic_cache
from pathlib import Path

detect = json.loads(Path('graphify-out/.graphify_detect.json').read_text(encoding=\"utf-8\"))
# В семантическое извлечение идут только контентные файлы. Код уже покрыт структурно
# проходом AST (Часть A); выравнивание здесь всех категорий заставило бы субагентов
# перечитывать каждый исходный файл (#1392). Видео сначала транскрибируется в документ на Шаге 2.5.
all_files = [f for cat in ('document', 'paper', 'image') for f in detect['files'].get(cat, [])]

cached_nodes, cached_edges, cached_hyperedges, uncached = check_semantic_cache(all_files)

# Всегда (пере)записывай файл кэша: при попаданиях записывай их, иначе УДАЛЯЙ любые
# остатки от предыдущего запуска, чтобы Часть C никогда не сливала устаревший .graphify_cached.json (#1392).
if cached_nodes or cached_edges or cached_hyperedges:
    Path('graphify-out/.graphify_cached.json').write_text(json.dumps({'nodes': cached_nodes, 'edges': cached_edges, 'hyperedges': cached_hyperedges}, ensure_ascii=False), encoding=\"utf-8\")
else:
    Path('graphify-out/.graphify_cached.json').unlink(missing_ok=True)
Path('graphify-out/.graphify_uncached.txt').write_text('\n'.join(uncached), encoding=\"utf-8\")
print(f'Cache: {len(all_files)-len(uncached)} files hit, {len(uncached)} files need extraction')
"
```

Отправляй субагентов только для файлов из `graphify-out/.graphify_uncached.txt`. Если все файлы закэшированы, сразу переходи к Части C.

**Шаг B1 - Разбей на чанки**

Загрузи файлы из `graphify-out/.graphify_uncached.txt`. Разбей на чанки по 20-25 файлов. Каждое изображение получает собственный чанк (vision требует отдельного контекста). При разбиении группируй файлы из одной директории, чтобы связанные артефакты попадали в один чанк и межфайловые связи извлекались с большей вероятностью.

**Шаг B2 - Отправь ВСЕ субагенты одним сообщением (Codex)**

> **Платформа Codex:** использует `spawn_agent` + `wait_agent` + `close_agent` вместо инструмента Agent.
> Требуется `multi_agent = true` под `[features]` в `~/.codex/config.toml`.
> Если `spawn_agent` недоступен, скажи пользователю добавить этот конфиг и перезапустить Codex.

Вызывай `spawn_agent` по одному разу на чанк — ВСЕ в том же ответе, чтобы они выполнялись параллельно. Формируй сообщение, оборачивая промпт извлечения в рамку делегирования задачи:

```
spawn_agent(agent_type="worker", message="Your task is to perform the following. Follow the instructions below exactly.\n\n<agent-instructions>\n[extraction prompt, with FILE_LIST, CHUNK_NUM, TOTAL_CHUNKS, DEEP_MODE substituted]\n</agent-instructions>\n\nExecute this now. Output ONLY the structured JSON response.")
```

После отправки всех агентов собери результаты последовательно в памяти:
```
result = wait_agent(handle); close_agent(handle)   # повторить для каждого handle
```

Разбирай каждый результат как JSON. Накапливай nodes/edges/hyperedges по всем результатам и записывай в `graphify-out/.graphify_semantic_new.json`. Codex собирает результаты в памяти, поэтому пер-чанковых файлов на диске нет; основанные на диске проверки успеха из Шага B3 не применимы — вместо них сигналом неудачи служит чанк, вернувший невалидный JSON.

Шаблон промпта субагента:

См. `references/extraction-spec.md` — там компактный промпт субагента (правила, формат node-ID, рубрика confidence, правила hyperedge и vision, JSON-схема). Загружай его только здесь и только когда хотя бы один чанк содержит doc, paper или изображение; корпус из чистого кода пропустил Часть B и никогда его не читает. Передавай каждому агенту этот промпт дословно с подставленными FILE_LIST, CHUNK_NUM, TOTAL_CHUNKS и DEEP_MODE и требуй от него инлайнового возврата JSON.

**Шаг B3 - Собери, закэшируй и объедини**

Дождись всех субагентов. Для каждого результата:
- Проверь, что `graphify-out/.graphify_chunk_NN.json` существует на диске — это сигнал успеха
- Если файл существует и содержит валидный JSON с `nodes` и `edges`, включи его и сохрани в кэш
- Если файл отсутствует, субагент, вероятно, был отправлен в режиме только чтения (тип Explore) — выведи предупреждение: «чанк N отсутствует на диске — возможно, субагент был read-only. Перезапусти с агентом general-purpose.» Не пропускай молча.
- Если субагент упал или вернул невалидный JSON, выведи предупреждение и пропусти этот чанк - не прерывайся

Если упало или отсутствует больше половины чанков, остановись и скажи пользователю перезапустить, проследив за использованием `subagent_type="general-purpose"`.

Слей все файлы чанков в `.graphify_semantic_new.json`. **После завершения каждого вызова Agent читай реальные количества токенов из поля `usage` результата инструмента Agent и записывай их обратно в JSON чанка перед слиянием** — в самом JSON чанка всегда стоят нули-заглушки. Затем запусти:
```bash
$(cat graphify-out/.graphify_python) -c "
import json, glob
from pathlib import Path

chunks = sorted(glob.glob('graphify-out/.graphify_chunk_*.json'))
all_nodes, all_edges, all_hyperedges = [], [], []
total_in, total_out = 0, 0
for c in chunks:
    d = json.loads(Path(c).read_text(encoding=\"utf-8\"))
    all_nodes += d.get('nodes', [])
    all_edges += d.get('edges', [])
    all_hyperedges += d.get('hyperedges', [])
    total_in += d.get('input_tokens', 0)
    total_out += d.get('output_tokens', 0)
Path('graphify-out/.graphify_semantic_new.json').write_text(json.dumps({
    'nodes': all_nodes, 'edges': all_edges, 'hyperedges': all_hyperedges,
    'input_tokens': total_in, 'output_tokens': total_out,
}, indent=2, ensure_ascii=False), encoding=\"utf-8\")
print(f'Merged {len(chunks)} chunks: {total_in:,} in / {total_out:,} out tokens')
"
```

Сохрани новые результаты в кэш:
```bash
$(cat graphify-out/.graphify_python) -c "
import json
from graphify.cache import save_semantic_cache
from pathlib import Path

new = json.loads(Path('graphify-out/.graphify_semantic_new.json').read_text(encoding=\"utf-8\")) if Path('graphify-out/.graphify_semantic_new.json').exists() else {'nodes':[],'edges':[],'hyperedges':[]}
saved = save_semantic_cache(new.get('nodes', []), new.get('edges', []), new.get('hyperedges', []))
print(f'Cached {saved} files')
"
```

Слей закэшированные + новые результаты в `graphify-out/.graphify_semantic.json`:
```bash
$(cat graphify-out/.graphify_python) -c "
import json
from pathlib import Path

cached = json.loads(Path('graphify-out/.graphify_cached.json').read_text(encoding=\"utf-8\")) if Path('graphify-out/.graphify_cached.json').exists() else {'nodes':[],'edges':[],'hyperedges':[]}
new = json.loads(Path('graphify-out/.graphify_semantic_new.json').read_text(encoding=\"utf-8\")) if Path('graphify-out/.graphify_semantic_new.json').exists() else {'nodes':[],'edges':[],'hyperedges':[]}

all_nodes = cached['nodes'] + new.get('nodes', [])
all_edges = cached['edges'] + new.get('edges', [])
all_hyperedges = cached.get('hyperedges', []) + new.get('hyperedges', [])
seen = set()
deduped = []
for n in all_nodes:
    if n['id'] not in seen:
        seen.add(n['id'])
        deduped.append(n)

merged = {
    'nodes': deduped,
    'edges': all_edges,
    'hyperedges': all_hyperedges,
    'input_tokens': new.get('input_tokens', 0),
    'output_tokens': new.get('output_tokens', 0),
}
Path('graphify-out/.graphify_semantic.json').write_text(json.dumps(merged, indent=2, ensure_ascii=False), encoding=\"utf-8\")
print(f'Extraction complete - {len(deduped)} nodes, {len(all_edges)} edges ({len(cached[\"nodes\"])} from cache, {len(new.get(\"nodes\",[]))} new)')
"
```
Удали временные файлы: `rm -f graphify-out/.graphify_cached.json graphify-out/.graphify_uncached.txt graphify-out/.graphify_semantic_new.json`

#### Часть C - Слияние AST + семантики в финальное извлечение

```bash
$(cat graphify-out/.graphify_python) -c "
import sys, json
from pathlib import Path

ast = json.loads(Path('graphify-out/.graphify_ast.json').read_text(encoding=\"utf-8\"))
sem = json.loads(Path('graphify-out/.graphify_semantic.json').read_text(encoding=\"utf-8\"))

# Слияние: сначала узлы AST, семантические узлы дедуплицируются по id
seen = {n['id'] for n in ast['nodes']}
merged_nodes = list(ast['nodes'])
for n in sem['nodes']:
    if n['id'] not in seen:
        merged_nodes.append(n)
        seen.add(n['id'])

merged_edges = ast['edges'] + sem['edges']
merged_hyperedges = sem.get('hyperedges', [])
merged = {
    'nodes': merged_nodes,
    'edges': merged_edges,
    'hyperedges': merged_hyperedges,
    'input_tokens': sem.get('input_tokens', 0),
    'output_tokens': sem.get('output_tokens', 0),
}
Path('graphify-out/.graphify_extract.json').write_text(json.dumps(merged, indent=2, ensure_ascii=False), encoding=\"utf-8\")
total = len(merged_nodes)
edges = len(merged_edges)
print(f'Merged: {total} nodes, {edges} edges ({len(ast[\"nodes\"])} AST + {len(sem[\"nodes\"])} semantic)')
"
```

### Шаг 4 - Построй граф, кластеризуй, проанализируй, сгенерируй выходные артефакты

**Перед началом:** код-блоки ниже передают `directed=IS_DIRECTED` в `build_from_json()`. Замени `IS_DIRECTED` на `True`, если передан `--directed` (строит `DiGraph`, сохраняющий направление рёбер источник→цель), иначе на `False` (неориентированный `Graph` по умолчанию). Подставляй это так же, как подставляешь `INPUT_PATH` — не оставляй литерал `IS_DIRECTED` в коде.

```bash
mkdir -p graphify-out
$(cat graphify-out/.graphify_python) -c "
import sys, json
from graphify.build import build_from_json
from graphify.cluster import cluster, score_all
from graphify.analyze import god_nodes, surprising_connections, suggest_questions
from graphify.report import generate
from graphify.export import to_json
from pathlib import Path

extraction = json.loads(Path('graphify-out/.graphify_extract.json').read_text(encoding=\"utf-8\"))
detection  = json.loads(Path('graphify-out/.graphify_detect.json').read_text(encoding=\"utf-8\"))

# root= зеркалит runbook --update (#1361): делай source_file относительным к той же базе,
# чтобы полная сборка и инкрементальный --update не расходились при повторном извлечении.
G = build_from_json(extraction, root='INPUT_PATH', directed=IS_DIRECTED)
# Защита ПЕРЕД любой записью: пустое извлечение не должно затирать хороший graph.json /
# GRAPH_REPORT.md / сайдкар анализа. Проверяй сразу после build (#1392).
if G.number_of_nodes() == 0:
    print('ERROR: Graph is empty - extraction produced no nodes.')
    print('Possible causes: all files were skipped, binary-only corpus, or extraction failed.')
    raise SystemExit(1)
communities = cluster(G)
cohesion = score_all(G, communities)
tokens = {'input': extraction.get('input_tokens', 0), 'output': extraction.get('output_tokens', 0)}
gods = god_nodes(G)
surprises = surprising_connections(G, communities)
labels = {cid: 'Community ' + str(cid) for cid in communities}
# Вопросы-заглушки - перегенерируются с реальными метками на Шаге 5
questions = suggest_questions(G, communities, labels)

# Экспортируй ПЕРВЫМ и уважай shrink-guard #479: to_json возвращает False (ничего
# не записывая), когда новый граф меньше существующего graph.json. Записывай
# GRAPH_REPORT.md + сайдкар анализа, только когда граф реально записан, чтобы
# они никогда не описывали граф, которого нет в graph.json (#1392).
wrote = to_json(G, communities, 'graphify-out/graph.json')
if not wrote:
    print('ERROR: refused to shrink graphify-out/graph.json (existing graph has more nodes; #479).')
    print('If this shrink is intentional (you deleted files), re-run a full build with --force.')
    raise SystemExit(1)
report = generate(G, communities, cohesion, labels, gods, surprises, detection, tokens, '.', suggested_questions=questions)
Path('graphify-out/GRAPH_REPORT.md').write_text(report, encoding=\"utf-8\")
analysis = {
    'communities': {str(k): v for k, v in communities.items()},
    'cohesion': {str(k): v for k, v in cohesion.items()},
    'gods': gods,
    'surprises': surprises,
    'questions': questions,
}
Path('graphify-out/.graphify_analysis.json').write_text(json.dumps(analysis, indent=2, ensure_ascii=False), encoding=\"utf-8\")
print(f'Graph: {G.number_of_nodes()} nodes, {G.number_of_edges()} edges, {len(communities)} communities')
"
```

Если этот шаг выводит `ERROR: Graph is empty`, остановись и сообщи пользователю, что произошло, - не переходи к разметке или визуализации.

Замени INPUT_PATH на фактический путь.

### Шаг 5 - Присвой метки сообществам

Прочитай `graphify-out/.graphify_analysis.json`. Для каждого ключа сообщества посмотри метки его узлов и напиши название из 2-5 слов на простом языке (например «Механизм внимания», «Пайплайн обучения», «Загрузка данных»).

Затем перегенерируй отчёт и сохрани метки для визуализатора:

```bash
$(cat graphify-out/.graphify_python) -c "
import sys, json
from graphify.build import build_from_json
from graphify.cluster import score_all
from graphify.analyze import god_nodes, surprising_connections, suggest_questions
from graphify.report import generate
from pathlib import Path

extraction = json.loads(Path('graphify-out/.graphify_extract.json').read_text(encoding=\"utf-8\"))
detection  = json.loads(Path('graphify-out/.graphify_detect.json').read_text(encoding=\"utf-8\"))
analysis   = json.loads(Path('graphify-out/.graphify_analysis.json').read_text(encoding=\"utf-8\"))

# root= как в Шаге 4 / runbook --update (#1361) — та же база для паритета ключей узлов.
G = build_from_json(extraction, root='INPUT_PATH', directed=IS_DIRECTED)
communities = {int(k): v for k, v in analysis['communities'].items()}
cohesion = {int(k): v for k, v in analysis['cohesion'].items()}
tokens = {'input': extraction.get('input_tokens', 0), 'output': extraction.get('output_tokens', 0)}

# LABELS - замени их на названия, выбранные выше
labels = LABELS_DICT

# Перегенерируй вопросы с реальными метками сообществ (метки влияют на формулировки вопросов)
questions = suggest_questions(G, communities, labels)

report = generate(G, communities, cohesion, labels, analysis['gods'], analysis['surprises'], detection, tokens, '.', suggested_questions=questions)
Path('graphify-out/GRAPH_REPORT.md').write_text(report, encoding=\"utf-8\")
Path('graphify-out/.graphify_labels.json').write_text(json.dumps({str(k): v for k, v in labels.items()}, ensure_ascii=False), encoding=\"utf-8\")
print('Report updated with community labels')
"
```

Замени `LABELS_DICT` на фактически сконструированный тобой dict (например `{0: "Механизм внимания", 1: "Пайплайн обучения"}`).
Замени INPUT_PATH на фактический путь.

### Шаг 6 - Сгенерируй Obsidian vault (opt-in) + HTML

**HTML генерируй всегда** (кроме случая `--no-viz`). **Obsidian vault — только если явно передан `--obsidian`** — иначе пропусти его: он создаёт по одному файлу на узел.

Если передан `--obsidian`:

- Если также передан `--obsidian-dir <path>`, передай его через `--dir`. Иначе по умолчанию `graphify-out/obsidian`.

```bash
graphify export obsidian
# или с собственной директорией: graphify export obsidian --dir ~/vaults/my-project
```

Сгенерируй HTML-граф (всегда, кроме случая `--no-viz`):

```bash
graphify export html  # автоагрегация до вида сообществ, если граф > 5000 узлов
# или: graphify export html --no-viz
```

### Шаги 6b-8 - Wiki, Neo4j, FalkorDB, SVG, GraphML, MCP, benchmark (только по их флагам)

Они запускаются только при наличии соответствующего флага (`--wiki`, `--neo4j`/`--neo4j-push`, `--falkordb`/`--falkordb-push`, `--svg`, `--graphml`, `--mcp`) или, для бенчмарка снижения расхода токенов, когда `total_words` превышает 5 000. Запуск по умолчанию без экспортных флагов пропускает их все. См. `references/exports.md` по каждому из них. Запускай любой экспорт `--wiki` до очистки Шага 9, чтобы `.graphify_labels.json` был ещё доступен.

---

### Шаг 9 - Сохрани манифест, обнови трекер затрат, приберись и отчитайся

```bash
$(cat graphify-out/.graphify_python) -c "
import json
from pathlib import Path
from datetime import datetime, timezone
from graphify.detect import save_manifest

# Сохрани манифест для --update
detect = json.loads(Path('graphify-out/.graphify_detect.json').read_text(encoding=\"utf-8\"))
# В режиме --update 'all_files' несёт полный корпус; 'files' — изменённое
# подмножество. Режим полной пересборки заполняет только 'files', фолбэк обрабатывает и это.
save_manifest(detect.get('all_files') or detect['files'])

# Обнови кумулятивный трекер затрат
extract = json.loads(Path('graphify-out/.graphify_extract.json').read_text(encoding=\"utf-8\"))
input_tok = extract.get('input_tokens', 0)
output_tok = extract.get('output_tokens', 0)

cost_path = Path('graphify-out/cost.json')
if cost_path.exists():
    cost = json.loads(cost_path.read_text(encoding=\"utf-8\"))
else:
    cost = {'runs': [], 'total_input_tokens': 0, 'total_output_tokens': 0}

cost['runs'].append({
    'date': datetime.now(timezone.utc).isoformat(),
    'input_tokens': input_tok,
    'output_tokens': output_tok,
    'files': detect.get('total_files', 0),
})
cost['total_input_tokens'] += input_tok
cost['total_output_tokens'] += output_tok
cost_path.write_text(json.dumps(cost, indent=2, ensure_ascii=False), encoding=\"utf-8\")

print(f'This run: {input_tok:,} input tokens, {output_tok:,} output tokens')
print(f'All time: {cost[\"total_input_tokens\"]:,} input, {cost[\"total_output_tokens\"]:,} output ({len(cost[\"runs\"])} runs)')
"
rm -f graphify-out/.graphify_detect.json graphify-out/.graphify_extract.json graphify-out/.graphify_ast.json graphify-out/.graphify_semantic.json graphify-out/.graphify_analysis.json
find graphify-out -maxdepth 1 -name '.graphify_chunk_*.json' -delete 2>/dev/null
rm -f graphify-out/.needs_update 2>/dev/null || true
```

Скажи пользователю (опусти строку obsidian, если не был передан --obsidian):
```
Граф готов. Результаты в PATH_TO_DIR/graphify-out/

  graph.html            - интерактивный граф, открой в браузере
  GRAPH_REPORT.md       - отчёт аудита
  graph.json            - сырые данные графа
  obsidian/             - Obsidian vault (только если был передан --obsidian)
```

Если graphify сэкономил тебе время, подумай о поддержке проекта: https://github.com/sponsors/safishamsi

Замени PATH_TO_DIR на фактический абсолютный путь обработанной директории.

Затем вставь эти разделы из GRAPH_REPORT.md прямо в чат:
- God Nodes
- Surprising Connections
- Suggested Questions

НЕ вставляй полный отчёт - только эти три раздела. Будь краток.

Затем сразу предложи исследование. Выбери единственный самый интересный предложенный вопрос из отчёта - тот, что пересекает больше всего границ сообществ или содержит самый неожиданный узел-мост - и спроси:

> «Самый интересный вопрос, на который может ответить этот граф: **[question]**. Проследить его?»

Если пользователь соглашается, запусти `/graphify query "[question]"` по графу и проведи его по ответу с опорой на структуру графа - какие узлы соединяются, какие границы сообществ пересекаются, что раскрывает путь. Продолжай, пока пользователю интересно исследовать. Каждый ответ должен заканчиваться естественным продолжением («это связано с X - копнуть глубже?»), чтобы сессия ощущалась как навигация, а не разовый отчёт.

Граф — это карта. Твоя задача после пайплайна — быть проводником.

---

## Защита интерпретатора для подкоманд

Перед запуском любой нижестоящей подкоманды (`--update`, `--cluster-only`, `query`, `path`, `explain`, `add`) проверь, что `.graphify_python` существует. Если его нет (например, пользователь удалил `graphify-out/`), сначала заново разреши интерпретатор:

```bash
if [ ! -f graphify-out/.graphify_python ]; then
    GRAPHIFY_BIN=$(which graphify 2>/dev/null)
    if [ -n "$GRAPHIFY_BIN" ]; then
        PYTHON=$(head -1 "$GRAPHIFY_BIN" | tr -d '#!')
        case "$PYTHON" in *[!a-zA-Z0-9/_.-]*) PYTHON="python3" ;; esac
    else
        PYTHON="python3"
    fi
    mkdir -p graphify-out
    "$PYTHON" -c "import sys; open('graphify-out/.graphify_python', 'w', encoding='utf-8').write(sys.executable)"
fi
```

## Для --update и --cluster-only

Обе — подкоманды не по умолчанию. `--update` повторно извлекает только новые или изменённые файлы; `--cluster-only` повторно запускает кластеризацию на существующем графе. См. `references/update.md` для обоих процессов.

---

## Для /graphify query

Когда `graphify-out/graph.json` уже существует и пользователь задаёт вопрос о корпусе, отвечай из графа, а не перестраивай его:

```bash
graphify query "<question>"
```

Перед обходом разверни вопрос по собственному словарю графа, чтобы несовпадение формулировок не превращало ответ в шум. Если CLI `graphify query` недоступен, откатись к инлайновому обходу NetworkX по `graphify-out/graph.json`. Отвечай, используя только содержимое вывода графа, и цитируй `source_location`, ссылаясь на конкретный факт. Об этом шаге расширения словаря, режимах обхода BFS/DFS, ограничении `--budget`, фолбэке NetworkX, обратной связи `save-result` и процессах `/graphify path` и `/graphify explain` см. `references/query.md`.

---

## Для /graphify add и --watch

Ни то, ни другое не входит в сборку по умолчанию. Когда пользователь запускает `/graphify add <url>`, чтобы загрузить URL в корпус, или передаёт `--watch` для автоперестроения при изменениях файлов, см. `references/add-watch.md`.

---

## Для commit-хука и нативной интеграции с CLAUDE.md

Когда пользователь просит установить хук автоперестроения post-commit или встроить graphify в CLAUDE.md проекта, см. `references/hooks.md`.

---

## Правила честности

- Никогда не выдумывай ребро. Если не уверен, используй AMBIGUOUS.
- Никогда не пропускай предупреждение о проверке корпуса.
- Всегда показывай в отчёте стоимость в токенах.
- Никогда не прячь оценки связности (cohesion) за символами - показывай исходное число.
- Никогда не запускай HTML-визуализацию на графе с более чем 5 000 узлами без предупреждения пользователя.
