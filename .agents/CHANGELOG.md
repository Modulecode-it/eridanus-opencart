# ChangeLog Системы Памяти

История эволюции самой архитектуры `.agents/` (не задач проекта — задачи в `INDEX.md`).

Формат: `Keep a Changelog`, даты в ISO (`YYYY-MM-DD`).

## [1.0.4] — 2026-08-18

### Changed
- **Переименование папки системы памяти: `.agent/` → `.agents/`** (задача 0.3-agents-folder-rename). Перенос выполнен через `git mv` — история файлов сохранена (статус `R`, 68 файлов). Все ссылки обновлены: внутри `.agents/`, в корневом `AGENTS.md` и в `agent-deploy-kit/` (193 замены), чтобы будущие развёртывания из кита создавали `.agents/`, а не старый путь.
- Корневой `AGENTS.md` переведён на русский (разделы «Система памяти агента» и «graphify»), пути ведут в `.agents/`.
- `.agents/templates/agents-root.md` и его копия в `agent-deploy-kit/core/templates/agents-root.md` переведены и зеркалируют корневой `AGENTS.md`.

### Added
- Скилл graphify полностью переведён на русский: `.agents/skills/graphify/SKILL.md` (641 строка) и все 8 файлов `references/`; копия в `agent-deploy-kit/optional/graphify/skill/` синхронизирована. YAML-frontmatter валиден, `name: graphify` и код-блоки не тронуты.
- Задача 0.3: спецификация, анализ, журнал реализации, итоговый отчёт.

### Notes
- `agent-memory-v2/` (+ zip, prompt — untracked прототип v2) не тронут: внутренняя `.agent/` относится к будущей миграции на v2.
- Долговечное знание: система памяти живёт в `.agents/`; новые документы и ссылки — только на `.agents/`.

## [1.0.3] — 2026-07-29

### Added
- Задача 1.5-apiship-install: установка модуля доставки ApiShip 1.2 (zip + распакованный исходник `apiship-1.2_3.x.ocmod/` в корне, по образцу задачи 1.4). Зафиксировано долговечное знание: ApiShip — агрегатор, для подключения Яндекс Доставки нужны **два токена в двух местах** — OAuth-токен Яндекса `y0__...` вводится в ЛК `apiship.ru` (Службы доставки → Яндекс.Доставка), а **токен ApiShip** вводится в модуле OpenCart (`shipping_apiship_token`); модуль ставится через Extension Installer, файлы не раскладываются по `admin/catalog/system`.
- `PROJECT_STRUCTURE.md`: упоминание пакета `apiship-1.2_3.x.ocmod/` в списке модулей доставки.

## [1.0.2] — 2026-07-22

### Changed
- Уточнено правило деплоя: новые generated-файлы `system/storage/modification/**` игнорируются, чтобы обычный `git clean -fd` не удалял серверные OCMOD modifications; коммитить их нужно только осознанно через `git add -f`.
## [1.0.1] — 2026-07-22

### Added
- Правило проекта: не тестировать `eridanus.dev.modulecode.ru` из локальной среды Codex HTTP/браузерными запросами; проверка сайта выполняется только через git-коммиты и серверный deploy.
- Предупреждение для серверного деплоя OpenCart: `git clean -fd` может удалить `system/storage/modification/`, после чего требуется обновление модификаторов OpenCart и cache.

## [1.0.0] — 2026-07-20

Первая версия универсальной системы памяти `.agents/`, развёрнутая из `agent-deploy-kit/` v1.0.0. Заменила прежнюю `.codex/` в этом проекте.

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
- `graphify` skill и `hooks.json` перенесены из `.codex/` в `.agents/skills/graphify/` и `.agents/hooks.json`.
- `PROJECT_STRUCTURE.md` перенесён без содержательных изменений (он уникален для репо).

### Removed
- OpenCart-специфика из `RULES.md` и `CHECKLISTS.md` (перенесена в `STACK.md`).
- Прежняя папка `.codex/` после полного переноса содержимого в `.agents/`.

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
