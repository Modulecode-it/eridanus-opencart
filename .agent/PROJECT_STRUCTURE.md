# Структура Проекта

Последнее обновление: 2026-06-11

Используйте этот файл как первый источник контекста проекта. Не сканируйте весь репозиторий рекурсивно, если запрошенная задача этого не требует.

## Проект

- Платформа: OpenCart 3.0.3.2.
- Публичная точка входа: `index.php`.
- Точка входа админки: `admin/index.php`.
- Приложение catalog: `catalog/`.
- Приложение admin: `admin/`.
- Код ядра/framework: `system/`.
- Дополнительное приложение интеграции: `yaorder/`.
- Instagram widget/package: `inwidget/`.
- Runtime/backup storage: `storage79/`.
- Дамп web analytics: `webstat/`.

## Верхнеуровневая Структура

```text
.
|-- admin/                  админское приложение OpenCart
|-- catalog/                catalog/storefront приложение OpenCart
|-- .codex/                 правила Codex, память структуры и документация задач
|-- inwidget/               отдельный widget с composer.json
|-- storage79/              большой backup storage/log/modification; по умолчанию не сканировать
|-- system/                 ядро OpenCart, библиотеки, storage, OCMOD XML-файлы
|-- webstat/                AWStats/static analytics reports; по умолчанию не сканировать
|-- yaorder/                кастомное приложение интеграции заказов Yandex
|-- .htaccess               Apache rewrite и deny rules
|-- config.php              config catalog; содержит environment-specific constants
|-- admin/config.php        config admin; содержит environment-specific constants
|-- index.php               bootstrap catalog, VERSION 3.0.3.2
|-- robots.txt              правила search crawler
`-- index.xml               artifact sitemap/static feed
```

## Документация Задач Codex

Новые нетривиальные задачи нужно документировать с одним общим task id в четырех папках. Основной текст документов писать на русском языке; технические пути и идентификаторы оставлять без перевода.

```text
.codex/
|-- PROJECT_STRUCTURE.md
|-- specs/                  требования задачи и критерии приемки
|-- analysis/               заметки исследования, текущее поведение, риски, решения
|-- implementation/         журналы реализации и важные изменения кода
`-- reports/                итоговый результат, проверка и известные пробелы
```

Пример набора файлов задачи:

```text
.codex/specs/1.1-checkout-flow.md
.codex/analysis/1.1-current-checkout-analysis.md
.codex/implementation/1.1-implementation-log.md
.codex/reports/1.1-final-report.md
```

## Основные Области OpenCart

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

## Важные Кастомные И Сторонние Модули

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
- `yaorder/` кастомный workflow заказов Yandex и automation scripts.

## OCMOD И Сгенерированный Код

- Исходные OCMOD-файлы:
  - `system/availpro.ocmod.xml`
  - `system/tweak.ocmod.xml`
  - `system/__fix_theme_editor.ocmod.xml`
- Директории сгенерированных modifications:
  - `system/storage/modification/`
  - `storage79/modification/`

Не редактируйте сначала сгенерированные modification-файлы. Предпочитайте исходные controllers/models/templates или OCMOD XML, затем обновляйте modifications в OpenCart.

## Пути, Которых Нужно Избегать По Умолчанию

Эти пути большие, сгенерированные, чувствительные или малоценные для обычной работы с кодом:

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

## Заметки Безопасности Из Первичного Ревью

- `user.php` создает admin user с hardcoded password. Считать критичным.
- `phpinfo.php` раскрывает конфигурацию PHP/server.
- Инструменты, похожие на Adminer, находятся в web root.
- `yaorder/config.php` содержит `APP_ID` и `APP_PASSWORD`.
- `yaorder/*.token` содержит token files.
- `storage79/logs/` содержит большие логи и, вероятно, operational data.

## Рекомендуемая Область Поиска

Для большинства задач сначала ищите только в этих путях:

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

Используйте точечный поиск в `storage79/` только при отладке сгенерированных modifications, старого runtime behavior или логов.
