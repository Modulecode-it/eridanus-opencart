# Справочник graphify: дополнительные экспорты и бенчмарк

Загружай этот файл, когда пользователь передал один из флагов экспорта (`--wiki`, `--neo4j`, `--neo4j-push`, `--falkordb`, `--falkordb-push`, `--svg`, `--graphml`, `--mcp`) или когда корпус достаточно велик для бенчмарка сокращения токенов. Каждый шаг выполняется только для своего флага.

### Шаг 6b — Wiki (только при флаге --wiki)

**Выполняй этот шаг, только если `--wiki` был явно указан в исходной команде.**

Запусти это до Шага 9 (очистка), чтобы `.graphify_labels.json` был ещё доступен.

```bash
graphify export wiki
```

### Шаг 7 — экспорт в Neo4j (только при флаге --neo4j или --neo4j-push)

**Если `--neo4j`** — сгенерируй Cypher-файл для ручного импорта:

```bash
graphify export neo4j
```

**Если `--neo4j-push <uri>`** — отправь данные напрямую в запущенный экземпляр Neo4j. Запроси у пользователя учётные данные, если они не предоставлены:

```bash
graphify export neo4j --push bolt://localhost:7687 --user neo4j --password PASSWORD
```

URI по умолчанию — `bolt://localhost:7687`, пользователь по умолчанию — `neo4j`. Используется MERGE — можно безопасно запускать повторно без создания дубликатов.

### Шаг 7a — экспорт в FalkorDB (только при флаге --falkordb или --falkordb-push)

**Если `--falkordb`** — сгенерируй Cypher-файл. Инструкции написаны на OpenCypher, но `GRAPH.QUERY` FalkorDB выполняет по одной инструкции за раз (нет массового импорта скриптом, как `cypher-shell` в Neo4j), поэтому для загрузки графа предпочитай `--falkordb-push`. Используй это, только когда нужен переносимый артефакт `cypher.txt`:

```bash
graphify export falkordb
```

**Если `--falkordb-push <uri>`** — отправь данные напрямую в запущенный экземпляр FalkorDB. Учётные данные опциональны; спрашивай у пользователя, только если экземпляр требует аутентификации:

```bash
graphify export falkordb --push falkordb://localhost:6379
```

URI по умолчанию — `falkordb://localhost:6379` (схема информационная — `redis://` или просто `host:port` тоже подойдут), аутентификация опциональна, а целевой граф по умолчанию — `graphify`. Используется MERGE — можно безопасно запускать повторно без создания дубликатов.

### Шаг 7b — экспорт SVG (только при флаге --svg)

```bash
graphify export svg
```

### Шаг 7c — экспорт GraphML (только при флаге --graphml)

```bash
graphify export graphml
```

### Шаг 7d — MCP-сервер (только при флаге --mcp)

```bash
$(cat graphify-out/.graphify_python) -m graphify.serve graphify-out/graph.json
```

Это запускает stdio MCP-сервер, предоставляющий инструменты: `query_graph`, `get_node`, `get_neighbors`, `get_community`, `god_nodes`, `graph_stats`, `shortest_path`. Добавь его в Claude Desktop или любой MCP-совместимый оркестратор агентов, чтобы другие агенты могли запрашивать граф в реальном времени.

Для настройки в Claude Desktop добавь запись в `claude_desktop_config.json`. Claude Desktop не умеет выполнять `$(...)`, а при `uv tool install` системный `python3` не может импортировать graphify — поэтому установи в `command` **абсолютный путь к интерпретатору**, который выводит `cat graphify-out/.graphify_python`:
```json
{
  "mcpServers": {
    "graphify": {
      "command": "<absolute path from: cat graphify-out/.graphify_python>",
      "args": ["-m", "graphify.serve", "/absolute/path/to/graphify-out/graph.json"]
    }
  }
}
```

### Шаг 8 — бенчмарк сокращения токенов (только если total_words > 5000)

Если `total_words` из `graphify-out/.graphify_detect.json` больше 5 000, выполни:

```bash
graphify benchmark
```

Выведи результат прямо в чат. Если `total_words <= 5000`, пропусти молча — для небольших корпусов ценность графа в структурной ясности, а не в сжатии токенов.
