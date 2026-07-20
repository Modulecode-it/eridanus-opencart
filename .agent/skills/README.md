# Скиллы

Опциональная папка для агентских скиллов. **Скиллы не входят в универсальное ядро** — они подключаются по необходимости под конкретный проект.

## Структура скилла

```text
skills/
└── <skill-name>/
    ├── SKILL.md          ← frontmatter: name, description + инструкция
    └── references/       ← (опционально) вспомогательные доки
```

## Установка скилла

1. **Готовые скиллы из кита** — см. `agent-deploy-kit/optional/` (например `graphify/`).
2. **Перенос существующих скиллов проекта** — см. `agent-deploy-kit/DEPLOY.md`, раздел «Перенос существующих скиллов».
3. **Свой скилл** — создай папку `<name>/`, добавь `SKILL.md` с frontmatter `name` + `description`.

## Правила переноса существующих скиллов

- Сохраняй `name` и `description` в frontmatter как есть — это триггеры активации.
- Копируй подпапку `references/` если есть.
- Не ломай структуру: один скилл = одна папка.
- Задокументируй перенесённые скиллы списком ниже.

## Активные скиллы этого проекта

- `graphify/` — knowledge graph кодовой базы (god nodes, community detection, cross-file relationships). Вывод в `graphify-out/`. Подключён через `../hooks.json` (PreToolUse на Bash → `graphify hook-check`). Команды: `/graphify`, `graphify query "<q>"`, `graphify path "<A>" "<B>"`, `graphify explain "<concept>"`, `graphify update .`.
