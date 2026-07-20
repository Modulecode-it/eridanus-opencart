# Онбординг: Внедрение Системы Памяти в Новый Проект

Пошаговый гайд. Подробный **мастер-промт** для агента — в `agent-deploy-kit/DEPLOY.md`. Здесь — что и зачем делает каждый шаг.

## Предусловия

- Есть корень репозитория проекта (любой стек: Laravel, OpenCart, Node, Python…).
- Установлен git.
- (Опционально) Папка `agent-deploy-kit/` уже лежит в репозитории или доступна для копирования.

## Шаг 1. Развёртывание ядра `.agent/`

Скопировать содержимое `agent-deploy-kit/core/` → `<project-root>/.agent/`.

Получится структура:
```text
.agent/
├── README.md  CORE.md  RULES.md  CHECKLISTS.md
├── STACK.example.md  INDEX.md  CHANGELOG.md  ONBOARDING.md
├── hooks.json.example
├── specs/ analysis/ implementation/ reports/ decisions/
├── templates/ prompts/ skills/
```

## Шаг 2. Выбор стека → `STACK.md`

1. Посмотреть готовые адаптеры в `agent-deploy-kit/stacks/` (`opencart.md`, `laravel.md`).
2. Если подходящий есть — скопировать как `.agent/STACK.md` и при необходимости адаптировать.
3. Если нет — скопировать `STACK.example.md` → `STACK.md` и заполнить секции под свой стек.

`STACK.md` — **единственный** файл ядра, привязанный к стеку.

## Шаг 3. Генерация `PROJECT_STRUCTURE.md`

Создать `.agent/PROJECT_STRUCTURE.md` (шаблон — `agent-deploy-kit/core/templates/project-structure.md`, если есть; иначе с нуля) и заполнить:
- название проекта, стек, версию;
- верхнеуровневое дерево директорий (без `vendor/`, `node_modules/`, `storage/`, `.git/`);
- ключевые модули/домены и их владельцев;
- пути, по умолчанию не сканировать;
- критичные файлы/секреты.

Этот файл **уникален для каждого репо** — его нельзя унаследовать из кита.

## Шаг 4. Перенос существующих скиллов

Если в проекте уже есть скиллы (`.claude/skills/`, `.cursor/skills/`, `skills/`, `.codex/skills/` и т.п.) — **аккуратно** перенести:

1. Инвентаризацияовать все найденные `SKILL.md` файлы.
2. Для каждого: скопировать папку скилла в `.agent/skills/<name>/`, сохранить frontmatter (`name`, `description`) и подпапку `references/`.
3. **Не ломать триггеры**: оставить `name` и `description` как есть.
4. Задокументировать перенос в `.agent/skills/README.md`.

## Шаг 5. Опциональные плагины

- **graphify** (knowledge graph): если нужен — см. `agent-deploy-kit/optional/graphify/README.md`. Копирует skill в `.agent/skills/graphify/`, подключает `hooks.json` (PreToolUse на Bash).
- Другие плагины: добавлять в `.agent/skills/<name>/` по тому же принципу.

## Шаг 6. Корневой `AGENTS.md`

Создать или обновить `<project-root>/AGENTS.md` — компактную точку входа для любого агента. Содержание:
- ссылка на `.agent/README.md` и порядок чтения;
- краткая сводка ключевых правил (язык, task-id, секреты);
- упоминание активных плагинов (graphify, и т.д.).

Шаблон — `agent-deploy-kit/core/templates/agents-root.md` (если есть) или см. пример в `DEPLOY.md`.

## Шаг 7. Инициализация `INDEX.md` и первый коммит

1. Проверить, что `.agent/INDEX.md` пустой и готов к работе.
2. Проверить `.agent/CHANGELOG.md` — добавить запись о развёртывании.
3. Закоммитить `.agent/` + `AGENTS.md` отдельным коммитом: `chore(agent): deploy agent memory system`.

## Шаг 8. Первая задача (проверка)

Создать служебную задачу `0.1-deploy-agent-memory` со всеми 4 документами, фиксирующими сам факт внедрения. Это заодно проверит, что цикл работает.

---

## Чек-лист готовности

- [ ] `.agent/` развёрнут из `core/`.
- [ ] `.agent/STACK.md` заполнен под стек проекта.
- [ ] `.agent/PROJECT_STRUCTURE.md` описывает конкретный репо.
- [ ] Существующие скиллы перенесены в `.agent/skills/`.
- [ ] `AGENTS.md` в корне ссылается на `.agent/`.
- [ ] `INDEX.md` и `CHANGELOG.md` инициализированы.
- [ ] Первый коммит сделан.
- [ ] (Опционально) graphify подключён.
- [ ] Служебная задача `0.1-deploy-agent-memory` создана.
