# Справочник graphify: query, path, explain

Загружай этот файл, когда пользователь задаёт вопрос по существующему графу или запускает `/graphify path` или `/graphify explain`. Заглушка query в ядре ссылается сюда за полным сценарием обхода. Эти сценарии используют CLI `graphify query`, когда он доступен, а иначе — встроенный обход на NetworkX.

Два режима обхода — выбирай в зависимости от вопроса:

| Режим | Флаг | Лучше всего для |
|------|------|----------|
| BFS (по умолчанию) | _(нет)_ | «С чем связан X?» — широкий контекст, сначала ближайшие соседи |
| DFS | `--dfs` | «Как X достигает Y?» — трассировка конкретной цепочки или пути зависимостей |

Сначала проверь, что граф существует:
```bash
$(cat graphify-out/.graphify_python) -c "
from pathlib import Path
if not Path('graphify-out/graph.json').exists():
    print('ERROR: No graph found. Run /graphify <path> first to build the graph.')
    raise SystemExit(1)
"
```
Если проверка не прошла, остановись и скажи пользователю сначала запустить `/graphify <path>`.

### Шаг 0 — Ограниченное расширение запроса (ОБЯЗАТЕЛЬНО перед обходом)

CLI `query` в graphify сопоставляет узлы через подстроки без учёта регистра + IDF — внутри бинарника **нет стемминга, синонимов и межъязыкового сопоставления**, и встроенный fallback ниже сопоставляет так же. Если вопрос пользователя использует другой язык или другой предметный словарь, чем метки графа (пользователь говорит «обработчик» / граф говорит «handler»; пользователь говорит «authentication» / граф говорит «Guardian»), буквальный матчер вернёт 0 совпадений, и ответ выродится в шум.

Исправь это **без выдумывания токенов**, сначала расширив запрос по фактическому словарю графа:

1. Извлеки токенный словарь из меток узлов:
```bash
$(cat graphify-out/.graphify_python) -c "
import json, re
from pathlib import Path
data = json.loads(Path('graphify-out/graph.json').read_text())
vocab = set()
for n in data['nodes']:
    for c in re.findall(r'[^\W\d_]+', n.get('label','') or '', re.UNICODE):
        parts = re.findall(r'[A-Z]+(?=[A-Z][a-z])|[A-Z]?[a-z]+|[A-Z]+', c) or [c]
        for p in parts:
            t = p.lower()
            if 3 <= len(t) <= 30:
                vocab.add(t)
Path('graphify-out/.vocab.txt').write_text('\n'.join(sorted(vocab)))
print(f'vocab: {len(vocab)} tokens')
"
```

2. Прочитай `graphify-out/.vocab.txt`. Затем для вопроса пользователя выбери **до 12 токенов строго из этого списка**, семантически соответствующих замыслу запроса. Жёсткие ограничения:
   - Выбирай ТОЛЬКО токены, присутствующие в файле словаря. НЕ выдумывай токены.
   - Если для концепции из запроса нет подходящего токена в словаре, пропусти её — не подставляй почти-синоним из памяти модели.
   - Если **ни один** токен словаря вообще не соответствует запросу, выведи пустой список и скажи пользователю, что в корпусе нет релевантного словаря для этого вопроса. Не выдумывай поиск.
   - Переводи между языками: русское «аутентификация» → ищи `auth`, `credential`, `token`, `security` — только при их наличии в словаре.
   - Морфология: «handlers» отображается на `handler`, только если он есть; «todos» отображается на `todo`, только если он есть.

3. Явно покажи выбор пользователю перед выполнением запроса, чтобы расширение можно было проверить:
```
Запрос расширен (из словаря графа, N токенов): [token1, token2, ...]
```
Если список пуст, прямо скажи об этом и остановись — не переходи к обходу.

### Шаг 1 — Обход

Собери **расширенную строку запроса**, объединив выбранные токены пробелами. Используй именно её как `QUESTION` ниже — НЕ исходный вопрос пользователя. (Исходный вопрос сохраняется только для `save-result` в конце.)

Предпочитай CLI, если он установлен:
```bash
graphify query "QUESTION"
# или: graphify query "QUESTION" --dfs --budget 3000
```

Если CLI недоступен, загрузи `graphify-out/graph.json` и выполни обход встроенным способом:

1. Найди 1–3 узла, чьи метки лучше всего соответствуют расширенным токенам.
2. Выполни подходящий обход из каждого стартового узла.
3. Прочитай подграф — метки узлов, отношения рёбер, теги confidence, исходные расположения.
4. Отвечай, используя **только** то, что содержит граф. Цитируй `source_location`, когда ссылаешься на конкретный факт.
5. Если в графе недостаточно информации, так и скажи — не галлюцинируй рёбра.

```bash
$(cat graphify-out/.graphify_python) -c "
import sys, json
from networkx.readwrite import json_graph
import networkx as nx
from pathlib import Path

data = json.loads(Path('graphify-out/graph.json').read_text())
G = json_graph.node_link_graph(data, edges='links')

question = 'QUESTION'
mode = 'MODE'  # 'bfs' или 'dfs'
terms = [t.lower() for t in question.split() if len(t) >= 3]  # порог как у словаря; сохраняет api/jwt/ios (#1392)

# Найти стартовые узлы с наилучшим совпадением
scored = []
for nid, ndata in G.nodes(data=True):
    label = ndata.get('label', '').lower()
    score = sum(1 for t in terms if t in label)
    if score > 0:
        scored.append((score, nid))
scored.sort(reverse=True)
start_nodes = [nid for _, nid in scored[:3]]

if not start_nodes:
    print('No matching nodes found for query terms:', terms)
    sys.exit(0)

subgraph_nodes = set()
subgraph_edges = []

if mode == 'dfs':
    # DFS: идти по одному пути как можно глубже, прежде чем откатываться.
    # Ограничение глубины до 6, чтобы не обходить весь граф.
    visited = set()
    stack = [(n, 0) for n in reversed(start_nodes)]
    while stack:
        node, depth = stack.pop()
        if node in visited or depth > 6:
            continue
        visited.add(node)
        subgraph_nodes.add(node)
        for neighbor in G.neighbors(node):
            if neighbor not in visited:
                stack.append((neighbor, depth + 1))
                subgraph_edges.append((node, neighbor))
else:
    # BFS: исследовать всех соседей слой за слоем до глубины 3.
    frontier = set(start_nodes)
    subgraph_nodes = set(start_nodes)
    for _ in range(3):
        next_frontier = set()
        for n in frontier:
            for neighbor in G.neighbors(n):
                if neighbor not in subgraph_nodes:
                    next_frontier.add(neighbor)
                    subgraph_edges.append((n, neighbor))
        subgraph_nodes.update(next_frontier)
        frontier = next_frontier

# Вывод с учётом токенного бюджета: ранжирование по релевантности, обрезка по бюджету (~4 символа/токен)
token_budget = BUDGET  # по умолчанию 2000
char_budget = token_budget * 4

# Оценка узлов по пересечению термов для ранжированного вывода
def relevance(nid):
    label = G.nodes[nid].get('label', '').lower()
    return sum(1 for t in terms if t in label)

ranked_nodes = sorted(subgraph_nodes, key=relevance, reverse=True)

lines = [f'Traversal: {mode.upper()} | Start: {[G.nodes[n].get(\"label\",n) for n in start_nodes]} | {len(subgraph_nodes)} nodes']
for nid in ranked_nodes:
    d = G.nodes[nid]
    lines.append(f'  NODE {d.get(\"label\", nid)} [src={d.get(\"source_file\",\"\")} loc={d.get(\"source_location\",\"\")}]')
for u, v in subgraph_edges:
    if u in subgraph_nodes and v in subgraph_nodes:
        _raw = G[u][v]; d = next(iter(_raw.values()), {}) if isinstance(G, nx.MultiGraph) else _raw
        lines.append(f'  EDGE {G.nodes[u].get(\"label\",u)} --{d.get(\"relation\",\"\")} [{d.get(\"confidence\",\"\")}]--> {G.nodes[v].get(\"label\",v)}')

output = '\n'.join(lines)
if len(output) > char_budget:
    output = output[:char_budget] + f'\n... (truncated at ~{token_budget} token budget - use --budget N for more)'
print(output)
"
```

Замени `QUESTION` на **расширенную** строку запроса, `MODE` на `bfs` или `dfs`, а `BUDGET` на токенный бюджет (по умолчанию `2000` либо то, что задано флагом `--budget N`). Затем ответь на основе вывода подграфа выше, используя только то, что содержит граф.

После написания ответа сохрани его обратно в граф, чтобы он улучшал будущие запросы. Включи расширенные токены в текст `--answer` (например, `"Expanded from original query via vocab: [tokens]. Then traversed..."`), чтобы следующий `--update` извлёк историю расширения как узел графа:

```bash
$(cat graphify-out/.graphify_python) -m graphify save-result --question "ORIGINAL_QUESTION" --answer "ANSWER" --type query --nodes NODE1 NODE2
```

Замени `ORIGINAL_QUESTION` на дословный вопрос пользователя, `ANSWER` на полный текст своего ответа (содержащий след расширенных токенов), а `NODE1 NODE2` на список процитированных меток узлов. Это замыкает петлю обратной связи: следующий `--update` извлечёт этот вопрос-ответ как узел графа.

---

## Для /graphify path

Найди кратчайший путь между двумя именованными концепциями в графе. Предпочитай CLI, если он установлен:

```bash
graphify path "NODE_A" "NODE_B"
```

Если CLI недоступен, выполни встроенным способом:

```bash
$(cat graphify-out/.graphify_python) -c "
import json, sys
import networkx as nx
from networkx.readwrite import json_graph
from pathlib import Path

data = json.loads(Path('graphify-out/graph.json').read_text())
G = json_graph.node_link_graph(data, edges='links')

a_term = 'NODE_A'
b_term = 'NODE_B'

def find_node(term):
    term = term.lower()
    scored = sorted(
        [(sum(1 for w in term.split() if w in G.nodes[n].get('label','').lower()), n)
         for n in G.nodes()],
        reverse=True
    )
    return scored[0][1] if scored and scored[0][0] > 0 else None

src = find_node(a_term)
tgt = find_node(b_term)

if not src or not tgt:
    print(f'Could not find nodes matching: {a_term!r} or {b_term!r}')
    sys.exit(0)

try:
    path = nx.shortest_path(G, src, tgt)
    print(f'Shortest path ({len(path)-1} hops):')
    for i, nid in enumerate(path):
        label = G.nodes[nid].get('label', nid)
        if i < len(path) - 1:
            _raw = G[nid][path[i+1]]; edge = next(iter(_raw.values()), {}) if isinstance(G, nx.MultiGraph) else _raw
            rel = edge.get('relation', '')
            conf = edge.get('confidence', '')
            print(f'  {label} --{rel}--> [{conf}]')
        else:
            print(f'  {label}')
except nx.NetworkXNoPath:
    print(f'No path found between {a_term!r} and {b_term!r}')
except nx.NodeNotFound as e:
    print(f'Node not found: {e}')
"
```

Замени `NODE_A` и `NODE_B` на фактические названия концепций от пользователя. Затем объясни путь простым языком — что означает каждый переход и почему он значим.

После написания объяснения сохрани его обратно:

```bash
$(cat graphify-out/.graphify_python) -m graphify save-result --question "Path from NODE_A to NODE_B" --answer "ANSWER" --type path_query --nodes NODE_A NODE_B
```

---

## Для /graphify explain

Дай простое объяснение одного узла — всё, что с ним связано. Предпочитай CLI, если он установлен:

```bash
graphify explain "NODE_NAME"
```

Если CLI недоступен, выполни встроенным способом:

```bash
$(cat graphify-out/.graphify_python) -c "
import json, sys
import networkx as nx
from networkx.readwrite import json_graph
from pathlib import Path

data = json.loads(Path('graphify-out/graph.json').read_text())
G = json_graph.node_link_graph(data, edges='links')

term = 'NODE_NAME'
term_lower = term.lower()

# Найти узел с наилучшим совпадением
scored = sorted(
    [(sum(1 for w in term_lower.split() if w in G.nodes[n].get('label','').lower()), n)
     for n in G.nodes()],
    reverse=True
)
if not scored or scored[0][0] == 0:
    print(f'No node matching {term!r}')
    sys.exit(0)

nid = scored[0][1]
data_n = G.nodes[nid]
print(f'NODE: {data_n.get(\"label\", nid)}')
print(f'  source: {data_n.get(\"source_file\",\"unknown\")}')
print(f'  type: {data_n.get(\"file_type\",\"unknown\")}')
print(f'  degree: {G.degree(nid)}')
print()
print('CONNECTIONS:')
for neighbor in G.neighbors(nid):
    _raw = G[nid][neighbor]; edge = next(iter(_raw.values()), {}) if isinstance(G, nx.MultiGraph) else _raw
    nlabel = G.nodes[neighbor].get('label', neighbor)
    rel = edge.get('relation', '')
    conf = edge.get('confidence', '')
    src_file = G.nodes[neighbor].get('source_file', '')
    print(f'  --{rel}--> {nlabel} [{conf}] ({src_file})')
"
```

Замени `NODE_NAME` на концепцию, о которой спросил пользователь. Затем напиши объяснение из 3–5 предложений: что это за узел, с чем он связан и почему эти связи значимы. Используй исходные расположения как цитаты.

После написания объяснения сохрани его обратно:

```bash
$(cat graphify-out/.graphify_python) -m graphify save-result --question "Explain NODE_NAME" --answer "ANSWER" --type explain --nodes NODE_NAME
```
