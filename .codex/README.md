# Codex Context

Start every project task from these files:

1. `PROJECT_STRUCTURE.md` - saved structure and important modules.
2. `RULES.md` - repository-specific working rules.
3. `CHECKLISTS.md` - task checklists and verification steps.
4. Task documentation under `specs/`, `analysis/`, `implementation/`, and `reports/` when the task is non-trivial.

Default rule for this repository: do not scan the whole tree on each request. Use the saved structure map, then inspect only the specific paths relevant to the task.

For every non-trivial task, keep the four task documentation folders in sync:

- Save analysis results in `analysis/`.
- Document decisions and architectural choices in `analysis/` or the task spec.
- Document code and documentation changes in `implementation/`.
- Create the final task report in `reports/`.
- Do not delete existing `.codex` documentation as part of normal task work; append or update it.

Keep `.codex` current. When a task reveals durable project knowledge, add it to the right file before finishing:

- Structure, modules, entry points: `PROJECT_STRUCTURE.md`
- Workflow rules, exclusions, safety constraints: `RULES.md`
- Repeatable task steps and verification: `CHECKLISTS.md`
- Task requirements: `specs/<task-id>-<slug>.md`
- Task investigation notes: `analysis/<task-id>-<slug>-analysis.md`
- Task implementation progress: `implementation/<task-id>-implementation-log.md`
- Task outcome and verification: `reports/<task-id>-final-report.md`
- One-off notes that are not durable: do not save unless they will help future work

Every task implementation log must include a changed-file journal. For each changed file, record:

- File
- Reason
- Short description
- Possible side effects

Use this task documentation layout for new non-trivial work:

```text
.codex/
|-- PROJECT_STRUCTURE.md
|-- specs/
|   |-- 1.1-checkout-flow.md
|   |-- 1.2-pvz-map.md
|   `-- 1.3-payment-status.md
|-- analysis/
|   |-- 1.1-current-checkout-analysis.md
|   |-- 1.2-measoft-analysis.md
|   `-- 1.3-payment-investigation.md
|-- implementation/
|   |-- 1.1-implementation-log.md
|   |-- 1.2-implementation-log.md
|   `-- 1.3-implementation-log.md
`-- reports/
    |-- 1.1-final-report.md
    |-- 1.2-final-report.md
    `-- 1.3-final-report.md
```

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

