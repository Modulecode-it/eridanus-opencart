# ChangeLog Системы Памяти

История эволюции самой архитектуры `.agent/` (не задач проекта — задачи в `INDEX.md`).

Формат: `Keep a Changelog`, даты в ISO (`YYYY-MM-DD`).

## [1.0.1] — 2026-07-22

### Added
- Правило проекта: не тестировать `eridanus.dev.modulecode.ru` из локальной среды Codex HTTP/браузерными запросами; проверка сайта выполняется только через git-коммиты и серверный deploy.
- Предупреждение для серверного деплоя OpenCart: `git clean -fd` может удалить `system/storage/modification/`, после чего требуется обновление модификаторов OpenCart и cache.

## [1.0.0] — 2026-07-20

Первая версия универсальной системы памяти `.agent/`, развёрнутая из `agent-deploy-kit/` v1.0.0. Заменила прежнюю `.codex/` в этом проекте.

### Added
- `CORE.md` — выделенное описание 4-документного цикла отдельно от `RULES.md`.
- `STACK.md` — слот под стек (адаптер OpenCart 3.0.3.2 под этот проект).
- `INDEX.md` — реестр всех задач со статусами (0.1, 0.2, 1.1–1.4, ADR-001).
- `CHANGELOG.md` — история эволюции самой системы памяти.
- `ONBOARDING.md` — гайд внедрения в новый проект.
- `decisions/README.md` — конвенция ADR.
- `templates/` — пустые скелеты документов (spec/analysis/implementation/report/adr/agents-root/project-structure).
- `skills/README.md` — слот под опциональные скиллы с правилами аккуратного переноса.

### Changed
- Корневая папка системы памяти переименована: `.codex` → `.agent` (универсальное имя, без привязки к инструменту).
- Разделено универсальное ядро (`CORE.md`, `RULES.md`, `CHECKLISTS.md`) и стек-специфика (`STACK.md`).
- `prompts/` переименованы из «шаблонов» в инструкции агенту (`analyze.md`, `implement.md`, `audit.md`); пустые скелеты вынесены в `templates/`.
- Все документы задач 0.1, 0.2, 1.1–1.4 перенесены через `git mv` с сохранением истории.
- `graphify` skill и `hooks.json` перенесены из `.codex/` в `.agent/skills/graphify/` и `.agent/hooks.json`.
- `PROJECT_STRUCTURE.md` перенесён без содержательных изменений (он уникален для репо).

### Removed
- OpenCart-специфика из `RULES.md` и `CHECKLISTS.md` (перенесена в `STACK.md`).
- Прежняя папка `.codex/` после полного переноса содержимого в `.agent/`.

---

## Шаблон записи

```markdown
## [X.Y.Z] — YYYY-MM-DD

### Added
- <новая возможность>

### Changed
- <изменение>

### Removed
- <удалённое>

### Fixed
- <исправление>
```

---

## Шаблон записи

```markdown
## [X.Y.Z] — YYYY-MM-DD

### Added
- <новая возможность>

### Changed
- <изменение>

### Removed
- <удалённое>

### Fixed
- <исправление>
```
