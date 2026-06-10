# Codex Context

Start every project task from these files:

1. `PROJECT_STRUCTURE.md` - saved structure and important modules.
2. `RULES.md` - repository-specific working rules.
3. `CHECKLISTS.md` - task checklists and verification steps.

Default rule for this repository: do not scan the whole tree on each request. Use the saved structure map, then inspect only the specific paths relevant to the task.

Keep `.codex` current. When a task reveals durable project knowledge, add it to the right file before finishing:

- Structure, modules, entry points: `PROJECT_STRUCTURE.md`
- Workflow rules, exclusions, safety constraints: `RULES.md`
- Repeatable task steps and verification: `CHECKLISTS.md`
- One-off notes that are not durable: do not save unless they will help future work

Avoid broad recursive reads of:

- `storage79/`
- `webstat/`
- `.git/`
- `.idea/`
- `system/storage/cache/`
- `system/storage/logs/`
- `system/storage/modification/`
- `system/storage/session/`
- `system/storage/upload/`

If deeper context is needed, prefer targeted file reads and focused searches in the OpenCart source directories listed in `PROJECT_STRUCTURE.md`.

