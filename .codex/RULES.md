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
