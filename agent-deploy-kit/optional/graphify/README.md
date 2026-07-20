# Опциональный Плагин: Graphify

[graphify](https://github.com/) — утилита, строящая knowledge graph кодовой базы: god nodes, community detection, cross-file relationships. Хранит вывод в `graphify-out/`.

> **Не входит в универсальное ядро `.agent/`.** Подключается только если в проекте нужен knowledge graph.

## Что Внутри

- `skill/SKILL.md` — сам скилл graphify с frontmatter `name: graphify` (триггер активации — вопросы о кодовой базе).
- `skill/.graphify_version` — файл версии graphify.
- `skill/references/` — 8 поддерживающих документов (query, path, explain, update, hooks, и т.д.).
- `hooks.json.example` — пример подключения хука `PreToolUse` на `Bash` (вызывает `graphify hook-check` для инкрементального обновления графа).

## Установка В Проект

1. Убедиться, что graphify установлен в системе (например через `pip install graphify` или `graphify.EXE` в PATH на Windows).
2. Скопировать `skill/` → `.agent/skills/graphify/`:
   ```bash
   cp -r agent-deploy-kit/optional/graphify/skill .agent/skills/graphify
   ```
3. (Опционально) скопировать `hooks.json.example` → `.agent/hooks.json` и адаптировать путь к `graphify.EXE` под свою систему.
4. Подключить `.agent/hooks.json` в конфиге агента (PreToolUse).
5. В корневом `AGENTS.md` раскомментировать секцию `## graphify` (см. шаблон `core/templates/agents-root.md`).
6. Документировать подключение в `.agent/skills/README.md` (раздел «Активные скиллы»).
7. Запустить `/graphify` для первичной сборки графа (создаст `graphify-out/`).

## Использование

Подробно — в `skill/SKILL.md`. Кратко:

- `/graphify` — полный pipeline на текущей директории.
- `graphify query "<question>"` — BFS-обход графа, широкий контекст.
- `graphify path "<A>" "<B>"` — связи между сущностями.
- `graphify explain "<concept>"` — фокус на концепте.
- `graphify update .` — инкрементальное обновление (AST-only, без API-цены).

## Удаление

```bash
rm -rf .agent/skills/graphify .agent/hooks.json graphify-out/
```

И убрать секцию graphify из корневого `AGENTS.md` и из `.agent/skills/README.md`.

## Заметки

- Dirty-файлы в `graphify-out/` после hooks/инкрементальных апдейтов — норма, не повод пропускать graphify.
- `graphify-out/wiki/index.md` (если сгенерирован через `--wiki`) — предпочитать для широкой навигации.
- `graphify-out/GRAPH_REPORT.md` читать только для широкого архитектурного обзора.
