# Repository Rules

Last updated: 2026-06-10

These rules apply to work in this OpenCart repository.

## Context Loading

1. Read `.codex/README.md` and `.codex/PROJECT_STRUCTURE.md` before broad analysis.
2. Do not recursively scan the whole repository unless the task needs it.
3. Use targeted searches in likely source paths first.
4. Avoid generated, large, and sensitive directories by default:
   - `storage79/`
   - `webstat/`
   - `.git/`
   - `.idea/`
   - `system/storage/cache/`
   - `system/storage/logs/`
   - `system/storage/modification/`
   - `system/storage/session/`
   - `system/storage/upload/`

## Task Documentation Structure

Use this structure for new non-trivial tasks:

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

Task documentation rules:

1. Use one shared task id prefix across all task documents, for example `1.2`.
2. Store requirements and acceptance criteria in `.codex/specs/<task-id>-<slug>.md`.
3. Store current-state investigation, touched modules, risks, and decisions in `.codex/analysis/<task-id>-<slug>-analysis.md`.
4. Store implementation progress and important code changes in `.codex/implementation/<task-id>-implementation-log.md`.
5. Store the final outcome, changed files, verification, and known gaps in `.codex/reports/<task-id>-final-report.md`.
6. If the user gives a task id or filename, use it. If not, choose a short descriptive slug and keep the numbering consistent with existing task files.
7. Do not store secrets, tokens, full logs, customer data, or environment-specific credentials in task docs.
8. Do not delete existing documentation during normal task work. Append or revise the relevant section while preserving useful history.
9. Save all analysis results in `.codex/analysis/`.
10. Document all decisions and architectural decisions in the task analysis file or task spec.
11. Document every code or documentation change in `.codex/implementation/`.
12. Create or update the final report in `.codex/reports/` after completing the task.
13. Every implementation log entry for a changed file must include file, reason, short description, and possible side effects.

## Git And Existing Changes

1. Check `git status --short` before code edits.
2. Do not revert existing user changes unless explicitly requested.
3. Treat unrelated untracked or modified files as user-owned.
4. Prefer non-interactive git commands.

Current known working tree notes:

- `admin/controller/extension/extension/thon.py` is deleted in the working tree.
- `.idea/` is ignored by `.gitignore`.
- Several untracked copy files with broken Cyrillic names exist under `admin/model/`, `catalog/model/`, and `catalog/view/theme/default/`.

## OpenCart Change Rules

1. Prefer editing source files in `admin/`, `catalog/`, `system/`, or `yaorder/`.
2. Do not edit generated files in `system/storage/modification/` or `storage79/modification/` as the primary fix.
3. For OCMOD behavior, edit the relevant source `.ocmod.xml` file and refresh modifications in OpenCart.
4. Keep OpenCart 3.0.3.2 conventions: controllers load language/model/view, models access DB through `$this->db`, templates stay in Twig.
5. Preserve existing theme/admin visual conventions unless the task explicitly asks for redesign.

## Security Rules

1. Treat configs, tokens, logs, and admin tools as sensitive.
2. Do not expose real secrets in new docs or examples.
3. Do not add new public scripts that can mutate database state without authentication.
4. Flag these known critical files if they matter to the task:
   - `user.php`
   - `phpinfo.php`
   - `adminer-4.7.3-mysql.php`
   - `wldb.php`
   - `fP46rbbUAI3e2VFVpuhaTGcYUIHmjxGodAjuBkf2.php`
   - `yaorder/*.token`
   - `yaorder/config.php`

## Verification

1. Run `php -l` on changed PHP files when PHP is available.
2. Validate XML syntax when editing `.ocmod.xml`.
3. For storefront changes, check affected catalog route manually when possible.
4. For admin changes, check permissions, language keys, controller route, and template variables.
5. For checkout/order/payment/shipping changes, test the complete order flow or clearly state what was not tested.

## Codex Memory

Before finishing a task, update `.codex` if new durable knowledge was discovered:

- New module map or file ownership: update `PROJECT_STRUCTURE.md`.
- New rule, caveat, unsafe path, or workflow constraint: update `RULES.md`.
- Repeatable steps or verification flow: update `CHECKLISTS.md`.
