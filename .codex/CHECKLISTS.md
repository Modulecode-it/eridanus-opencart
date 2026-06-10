# Project Checklists

Last updated: 2026-06-10

Use these checklists for repeatable work in this OpenCart project.

## Before Any Code Change

1. Read `.codex/README.md`.
2. Read `.codex/PROJECT_STRUCTURE.md`.
3. Check `git status --short`.
4. Identify whether the task affects catalog, admin, OCMOD, integration, or runtime data.
5. Inspect only the focused files needed for the task.

## PHP Change

1. Locate the source controller/model/library file.
2. Check related language files and templates.
3. Keep OpenCart 3.0.3.2 patterns.
4. Avoid generated modification files as the primary edit target.
5. Run `php -l path/to/changed.php` when PHP is available.
6. Update `.codex` if the change reveals durable project knowledge.

## Catalog UI Or Template Change

1. Inspect the affected Twig template in `catalog/view/theme/default/template/`.
2. Inspect related stylesheet in `catalog/view/theme/default/stylesheet/`.
3. Check controller-provided variables before changing template logic.
4. Preserve existing theme conventions unless redesign is requested.
5. Verify desktop and mobile layout when possible.

## Admin UI Or Module Change

1. Inspect admin controller under `admin/controller/`.
2. Inspect admin model under `admin/model/` if data changes.
3. Inspect admin language file under `admin/language/`.
4. Inspect admin template under `admin/view/template/`.
5. Check route, permission key, form token/user token usage, and save/cancel URLs.
6. Run PHP lint on changed PHP files.

## OCMOD Change

1. Find the source `.ocmod.xml` in `system/`.
2. Confirm what target file and search operation it modifies.
3. Do not patch `system/storage/modification/` directly unless debugging.
4. Validate XML syntax.
5. After deployment, refresh OpenCart modifications and clear relevant cache.

## Shipping, Payment, Or Order Flow Change

1. Identify affected extension:
   - CDEK: `cdek_integrator`, `shipping/cdek`, `total/cdek`
   - Yandex: `yandex_marketplace`, `yandex_market`, `yaorder`
   - Modulbank: `payment/modulbank`
   - Measoft: `shipping/measoftcourier`
2. Inspect catalog and admin sides of the extension.
3. Check order status changes, totals, shipping quote calculation, and external API logging.
4. Test a full checkout/order scenario when possible.
5. State clearly if external API calls were not tested.

## Security Cleanup

1. Identify public root service files:
   - `user.php`
   - `phpinfo.php`
   - `adminer-4.7.3-mysql.php`
   - `wldb.php`
   - `fP46rbbUAI3e2VFVpuhaTGcYUIHmjxGodAjuBkf2.php`
2. Identify token/config files:
   - `config.php`
   - `admin/config.php`
   - `yaorder/config.php`
   - `yaorder/*.token`
3. Check `.htaccess` deny rules for the affected paths.
4. Prefer removing public access or moving tools outside the web root.
5. Rotate credentials if a real secret was exposed.

## Final Response Checklist

1. State what changed or what was found.
2. Mention files touched with links when useful.
3. Mention verification performed.
4. Mention verification not performed if relevant.
5. Suggest only concrete next steps that naturally follow from the task.

