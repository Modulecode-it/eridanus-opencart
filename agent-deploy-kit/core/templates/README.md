# Шаблоны Документов

Пустые скелеты для быстрого старта нового документа задачи. Скопируй нужный шаблон в соответствующую папку и заполни.

## Шаблоны

| Шаблон | Куда копировать | Имя файла |
|---|---|---|
| `spec-template.md` | `../specs/` | `<task-id>-<slug>.md` |
| `analysis-template.md` | `../analysis/` | `<task-id>-<slug>-analysis.md` |
| `implementation-template.md` | `../implementation/` | `<task-id>-implementation-log.md` |
| `report-template.md` | `../reports/` | `<task-id>-final-report.md` |
| `adr-template.md` | `../decisions/` | `ADR-NNN-<slug>.md` |
| `agents-root.md` | `<project-root>/` | `AGENTS.md` |
| `project-structure.md` | `../` (внутри `.agent/`) | `PROJECT_STRUCTURE.md` |

## Правила

- Шаблоны содержат **только структуру** (заголовки и подсказки), без содержательного текста.
- Инструкции агенту (что делать на каждом этапе) — в `../prompts/`.
- Не редактируйте шаблоны под конкретную задачу — копируйте и наполняйте копию.
