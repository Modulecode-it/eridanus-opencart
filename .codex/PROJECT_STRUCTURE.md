# Project Structure

Last updated: 2026-06-10

Use this file as the first source of project context. Do not recursively scan the whole repository unless the requested task requires it.

## Project

- Platform: OpenCart 3.0.3.2.
- Public entry point: `index.php`.
- Admin entry point: `admin/index.php`.
- Catalog app: `catalog/`.
- Admin app: `admin/`.
- Core/framework code: `system/`.
- Extra integration app: `yaorder/`.
- Instagram widget/package: `inwidget/`.
- Runtime/backup storage: `storage79/`.
- Web analytics dump: `webstat/`.

## Top-Level Layout

```text
.
|-- admin/                  OpenCart admin application
|-- catalog/                OpenCart catalog/storefront application
|-- .codex/                 Codex rules, structure memory, and task documentation
|-- inwidget/               Standalone widget with composer.json
|-- storage79/              Large storage/log/modification backup; avoid scanning by default
|-- system/                 OpenCart core, libraries, storage, OCMOD XML files
|-- webstat/                AWStats/static analytics reports; avoid scanning by default
|-- yaorder/                Custom Yandex order integration app
|-- .htaccess               Apache rewrite and deny rules
|-- config.php              Catalog config; contains environment-specific constants
|-- admin/config.php        Admin config; contains environment-specific constants
|-- index.php               Catalog bootstrap, VERSION 3.0.3.2
|-- robots.txt              Search crawler rules
`-- index.xml               Sitemap/static feed artifact
```

## Codex Task Documentation

New non-trivial tasks should be documented with one shared task id across four folders:

```text
.codex/
|-- PROJECT_STRUCTURE.md
|-- specs/                  Task requirements and acceptance criteria
|-- analysis/               Investigation notes, current behavior, risks, decisions
|-- implementation/         Implementation logs and important code changes
`-- reports/                Final outcome, verification, and known gaps
```

Example task file set:

```text
.codex/specs/1.1-checkout-flow.md
.codex/analysis/1.1-current-checkout-analysis.md
.codex/implementation/1.1-implementation-log.md
.codex/reports/1.1-final-report.md
```

## Main OpenCart Areas

```text
admin/
|-- controller/
|   |-- catalog/
|   |-- common/
|   |-- customer/
|   |-- design/
|   |-- extension/
|   |-- localisation/
|   |-- marketplace/
|   |-- report/
|   |-- sale/
|   `-- setting/
|-- language/
|-- model/
|-- view/
`-- index.php

catalog/
|-- controller/
|   |-- account/
|   |-- checkout/
|   |-- common/
|   |-- extension/
|   |-- information/
|   `-- product/
|-- language/
|-- model/
|-- view/
|   |-- javascript/
|   `-- theme/
|       |-- css/
|       `-- default/
`-- view/theme/default/
```

## Important Custom/Third-Party Modules

- `admin/controller/extension/module/cdek_integrator.php`
- `admin/controller/extension/module/filterit.php`
- `admin/controller/extension/module/dwebexporter.php`
- `admin/controller/extension/module/sms_alert.php`
- `admin/controller/extension/module/yandex_marketplace.php`
- `admin/controller/extension/module/yabuy.php`
- `admin/controller/extension/module/yabuy2.php`
- `admin/controller/extension/payment/modulbank.php`
- `admin/controller/extension/shipping/cdek.php`
- `admin/controller/extension/shipping/measoftcourier.php`
- `catalog/controller/extension/module/yandex_market.php`
- `catalog/controller/extension/module/yandex_market_dbs.php`
- `catalog/controller/extension/module/filterit.php`
- `catalog/controller/extension/module/sms_alert.php`
- `catalog/model/export/yandex_market.php`
- `yaorder/` custom Yandex order workflow and automation scripts.

## OCMOD And Generated Code

- Source OCMOD files:
  - `system/availpro.ocmod.xml`
  - `system/tweak.ocmod.xml`
  - `system/__fix_theme_editor.ocmod.xml`
- Generated modification directories:
  - `system/storage/modification/`
  - `storage79/modification/`

Do not edit generated modification files first. Prefer source controllers/models/templates or OCMOD XML, then refresh modifications in OpenCart.

## Paths To Avoid By Default

These are large, generated, sensitive, or low-value for normal code work:

- `.git/`
- `.idea/`
- `storage79/`
- `webstat/`
- `system/storage/cache/`
- `system/storage/logs/`
- `system/storage/modification/`
- `system/storage/session/`
- `system/storage/upload/`
- root Adminer/phpinfo/service files:
  - `adminer-4.7.3-mysql.php`
  - `wldb.php`
  - `fP46rbbUAI3e2VFVpuhaTGcYUIHmjxGodAjuBkf2.php`
  - `phpinfo.php`
  - `user.php`

## Security Notes From Initial Review

- `user.php` creates an admin user with a hardcoded password. Treat as critical.
- `phpinfo.php` exposes PHP/server configuration.
- Adminer-like database tools exist in the web root.
- `yaorder/config.php` contains `APP_ID` and `APP_PASSWORD`.
- `yaorder/*.token` contains token files.
- `storage79/logs/` contains large logs and likely operational data.

## Recommended Search Scope

For most tasks, search only these paths first:

```text
admin/controller/
admin/model/
admin/language/
admin/view/template/
catalog/controller/
catalog/model/
catalog/language/
catalog/view/theme/default/template/
catalog/view/theme/default/stylesheet/
system/library/
system/config/
system/*.ocmod.xml
yaorder/
```

Use targeted searches in `storage79/` only when debugging generated modifications, old runtime behavior, or logs.

