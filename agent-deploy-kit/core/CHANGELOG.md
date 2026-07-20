# ChangeLog Системы Памяти

История эволюции самой архитектуры `.agent/` (не задач проекта — задачи в `INDEX.md`).

Формат: `Keep a Changelog`, даты в ISO (`YYYY-MM-DD`).

## [Unreleased]

### Added
- Шаблон `STACK.example.md` для нового проекта.
- `CHANGELOG.md`, `INDEX.md`, `ONBOARDING.md` как часть ядра.
- `CORE.md` — выделенное описание 4-документного цикла отдельно от `RULES.md`.
- `templates/` с пустыми скелетами spec/analysis/implementation/report/adr.
- `stacks/opencart.md` и `stacks/laravel.md` — готовые адаптеры стека.
- `optional/graphify/` — опциональный плагин knowledge graph.

### Changed
- Разделено универсальное ядро (`CORE.md`, `RULES.md`, `CHECKLISTS.md`) и стек-специфика (`STACK.md`).
- `prompts/` переименованы из «шаблонов» в инструкции агенту; пустые скелеты вынесены в `templates/`.
- Корневая папка системы памяти переименована: `.codex` → `.agent` (универсальное имя, без привязки к инструменту).

### Removed
- OpenCart-специфика из `RULES.md` и `CHECKLISTS.md` (перенесена в `STACK.md`).

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
