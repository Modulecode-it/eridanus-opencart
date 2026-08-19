# Стек: OpenCart 3.0.3.2

Последнее обновление: 2026-08-18

Адаптер стека под конкретно этот проект (eridanus-opencart). Полная карта репозитория — в `PROJECT_STRUCTURE.md`.

## Стек и Версия

- Язык: PHP (версия сервера).
- Фреймворк: OpenCart 3.0.3.2 (`index.php` VERSION 3.0.3.2).
- Шаблонизатор: Twig.
- БД: MySQL/MariaDB.
- Модификации: OCMOD XML.
- Кастомные приложения: `yaorder/` (интеграция заказов Yandex), `inwidget/` (Instagram widget).
- Runtime/backup: `storage79/`.

## Структура Исходников

Краткая карта. Полная — в `PROJECT_STRUCTURE.md`.

```text
admin/        админское приложение OpenCart
catalog/      storefront приложение OpenCart
system/       ядро OpenCart, библиотеки, storage, OCMOD XML-файлы
yaorder/      кастомное приложение интеграции заказов Yandex
inwidget/     отдельный Instagram widget (с composer.json)
storage79/    большой backup storage/log/modification (по умолчанию не сканировать)
```

Типичный layout внутри `admin/` и `catalog/`:

```text
{admin,catalog}/
|-- controller/    PHP-контроллеры (загружают language/model/view)
|-- model/         модели, работа с БД через $this->db
|-- view/          шаблоны (admin: view/template/, catalog: view/theme/<theme>/template/)
`-- language/      языковые файлы (ru-ru/, russian/, en-gb/, english/)
```

## Команды

| Действие | Команда |
|---|---|
| PHP lint изменённого файла | `php -l path/to/file.php` |
| Проверить XML-синтаксис OCMOD | `xmllint --noout path/to/file.ocmod.xml` (или валидация в браузере) |

> В OpenCart нет встроенного тест-раннера или миграций в современном смысле. БД-схема меняется через `install/opencart.sql` или install-скрипты расширений. Изменения настроек — через админку (таблица `oc_setting`).

## Правила Изменений OpenCart

1. **Предпочитать исходные файлы** в `admin/`, `catalog/`, `system/` или кастомных приложениях (`yaorder/` и т.п.).
2. **Не редактировать сгенерированные** файлы в `system/storage/modification/` и `<backup>/modification/` как основное исправление. Только для отладки.
3. Для поведения OCMOD — редактировать соответствующий исходный `.ocmod.xml`, затем обновлять модификации в OpenCart (админка → Extensions → Modifications → Refresh).
4. Соблюдать соглашения OpenCart 3.0.x: контроллеры загружают language/model/view, модели работают с БД через `$this->db`, шаблоны остаются в Twig.
5. Сохранять существующие визуальные соглашения темы и админки, если задача явно не просит редизайн.
6. При правках расширений доставки/оплаты/потока заказа — проверять и catalog-, и admin-сторону.

## Изменение PHP

1. Найти исходный controller/model/library файл.
2. Проверить связанные языковые файлы и шаблоны.
3. Соблюдать паттерны OpenCart 3.0.x.
4. Не использовать сгенерированные modification-файлы как основную цель правки.
5. Запустить `php -l path/to/changed.php`, когда PHP доступен.

## Изменение Catalog UI Или Шаблона

1. Проверить затронутый Twig-шаблон в `catalog/view/theme/<theme>/template/`.
2. Проверить связанный stylesheet в `catalog/view/theme/<theme>/stylesheet/`.
3. Проверить переменные, которые передаёт контроллер, перед изменением логики шаблона.
4. Сохранять соглашения существующей темы, если не запрошен редизайн.
5. По возможности проверить desktop и mobile layout.

## Изменение Admin UI Или Модуля

1. Проверить admin controller в `admin/controller/`.
2. Проверить admin model в `admin/model/`, если меняются данные.
3. Проверить admin language file в `admin/language/`.
4. Проверить admin template в `admin/view/template/`.
5. Проверить route, permission key, form token/user token, URL сохранения и отмены.
6. Запустить PHP lint для изменённых PHP-файлов.

## Изменение OCMOD

1. Найти исходный `.ocmod.xml` в `system/`.
2. Подтвердить, какой target file и search operation он изменяет.
3. Не править `system/storage/modification/` напрямую, кроме отладки.
4. Проверить XML-синтаксис.
5. После деплоя обновить OpenCart modifications и очистить релевантный cache.

## Изменение Доставки, Оплаты Или Потока Заказа

1. Определить затронутое расширение. Установленные в этом проекте:
   - **CDEK**: `cdek_integrator`, `shipping/cdek`, `total/cdek`, payment `cod_cdek`.
   - **Measoft**: `shipping/measoftcourier`, `shipping/measoftcouriershipping`.
   - **Yandex**: `yandex_marketplace`, `yandex_market`, `yaorder/`.
   - **Modulbank**: `payment/modulbank`.
   - **Liqpay, PayPal (pp_express/pp_pro/pp_standard)**: payment extensions.
   - **filterit**, **sms_alert**: вспомогательные модули, влияющие на totals/payment.
2. Проверить catalog- и admin-стороны расширения.
3. Проверить изменения статуса заказа, totals, расчёт shipping quote и логирование внешнего API.
4. По возможности протестировать полный сценарий checkout/order.
5. Ясно указать, если внешние API-вызовы не тестировались.

## Проверка

1. `php -l <file>` для изменённых PHP-файлов.
2. Проверка XML-синтаксиса при правках `.ocmod.xml`.
3. Runtime-проверки витрины и админки — через браузер на dev-сайте (плагин browser-use; см. секцию «Браузерный Доступ к Dev-Сайту (AI)»). SSH к серверу недоступен — серверные шаги (git pull, cron) выполняет пользователь.
4. Для изменений админки — права, языковые ключи, route контроллера, переменные шаблона.
5. Для изменений checkout/order/payment/shipping — полный поток заказа или явное указание, что не проверялось.

## Деплой На Сервер

1. Dev-сервер (`eridanus.dev.modulecode.ru`) — Docker-проект, доступ только по HTTP(S) через браузер. **SSH недоступен и не запрашивать** (на том же сервере чужие сайты). Серверные шаги (`git fetch`/`merge` на сервере, cron, рестарты) выполняет пользователь по инструкции агента.
2. На сервере не использовать `git clean -fd` без явного понимания последствий. Новые файлы `system/storage/modification/**` игнорируются в `.gitignore`, чтобы обычный `git clean -fd` их не удалял; `git clean -fdx` всё равно удалит ignored-файлы.
3. Если generated modification нужно сохранить в репозитории, добавлять его только осознанно через `git add -f system/storage/modification/...`.
4. После удаления или обновления `system/storage/modification/` нужно заново обновить модификаторы OpenCart в админке и очистить релевантный cache.
5. Если deploy упирается в локальные неотслеживаемые `.agents/`/`agent-deploy-kit/` файлы или права, сначала исправить владельца/права на сервере, а не удалять runtime-файлы OpenCart вслепую.

## Браузерный Доступ к Dev-Сайту (AI)

Разрешён владельцем с 2026-08-19 (задача 0.5). Инструмент — плагин browser-use
(скиллы `control-browser` / `web-gui-tester` + MCP `node_repl`).

### Что можно

- Открывать витрину и админку `eridanus.dev.modulecode.ru`: навигация, чтение
  состояния, настройка модулей через формы, обновление OCMOD-модификаторов,
  очистка кэша, тестовые заказы, скриншоты для верификации.
- Создавать тестовые заказы/сущности, помечая их как тестовые (например, имя
  «TEST AI»).

### Креды

- Логин/пароль админки — в `.secrets/dev-admin.md` (папка `.secrets/` в
  `.gitignore`; в `.agents` и в git секреты не попадают).
- Использовать **отдельного AI-пользователя** админки (создаётся владельцем),
  не основной аккаунт.

### Бекапы (обязательное правило владельца)

- **Перед важными/разрушающими операциями** (удаление записей/файлов,
  установка или удаление модулей, массовые правки, SQL-запросы, смена настроек,
  затрагивающих много данных) — сначала предложить пользователю забекапиться и
  дождаться подтверждения.
- Способы: System → Maintenance → Backup → Export (дамп таблиц из админки) либо
  Adminer (`adminer-4.7.3-mysql.php`) — полный SQL-дамп. Дампы скачиваются
  локально и в git не попадают.
- Рутинные операции (просмотр, включение уже установленного модуля, заполнение
  форм настроек) бекапа не требуют, но при сомнении — предложить.

### Ограничения

- База — копия боевой: реальные заказы и данные клиентов не изменять и не
  публиковать (скриншоты с персональными данными — не выносить за пределы
  сессии, в git не коммитить).
- Боевые секреты (токены продакшена) в dev не вводить без указания владельца.
- Скриншоты и дампы БД не коммитить в репозиторий.
- Действия вне dev-домена (ЛК apiship.ru, кабинет Яндекса и т.п.) — только по
  явной просьбе пользователя и с его секретами, вводимыми им самим.

## Синхронизация Модулей, Установленных На Сервере (чек-лист)

После установки модуля через админку (Extension Installer) на сервере появляются
untracked/modified файлы. Порядок приведения в git (задача 1.6):

1. **Диагностика на сервере**: `git diff --summary` (mode changes), `git diff --ignore-cr-at-eol --stat` (реальный контент), `git log --oneline -3` (не отстаёт ли сервер от main — fetch мог не делаться).
2. **Классифицировать**: mode change `755→644` и CRLF-шум — НЕ коммитить; перезапись инсталлером кастомных патчей — восстанавливать из HEAD (`git checkout -- <файлы>`).
3. **Сверить untracked с пакетом** в репо (`cmp`/`md5sum` против `<пакет>.ocmod/upload/<path>`); при совпадении — раскладывать по рабочим путям ЛОКАЛЬНО из пакета, файлы с сервера не переносить.
4. **Рабочие пути модулей трекаются в git** (модель задач 1.4/1.5/1.6: пакет + разложенные файлы).
5. **Сервер после пуша**: `git checkout -- .` (вернуть патчи/шум) → `git stash push -u` (спрятать untracked, иначе merge откажется) → `git fetch origin && git merge --ff-only origin/main` → `git status` (ожидается чисто) → `git stash drop`. Не использовать `git clean -fd`.
6. **Картинки модулей**: правило `.gitignore` — только `/image/` (корневая, товары). Вложенные `image/` (ассеты модулей) версионировать; при добавлении пакета `.ocmod/` проверять, что картинки пакета попали в git.

## Пути, По Умолчанию Не Сканировать

- `storage79/` (или аналог — большой backup/log/modification storage)
- `webstat/` (аналитика)
- `system/storage/cache/`
- `system/storage/logs/`
- `system/storage/modification/`
- `system/storage/session/`
- `system/storage/upload/`
- `.git/`, `.idea/`
- Корневые Adminer/phpinfo/service файлы при наличии.

## Критичные Файлы И Секреты

- `config.php`, `admin/config.php` — environment-specific constants.
- `yaorder/config.php` — внешние `APP_ID`/`APP_PASSWORD`.
- `yaorder/*.token` — token files.
- Корневые админ-скрипты в web root — критичные:
  - `user.php` (создаёт admin user с hardcoded password — критично).
  - `phpinfo.php` (раскрывает конфигурацию PHP/server).
  - `adminer-4.7.3-mysql.php`.
  - `wldb.php`.
  - `fP46rbbUAI3e2VFVpuhaTGcYUIHmjxGodAjuBkf2.php`.
- `storage79/logs/` — большие логи, вероятно operational data.

## Очистка Безопасности (если задача про безопасность)

1. Определить публичные service-файлы в корне (`user.php`, `phpinfo.php`, `adminer-*.php`, и т.п.).
2. Определить token/config-файлы (`config.php`, `admin/config.php`, `<custom-app>/config.php`, `*.token`).
3. Проверить deny rules в `.htaccess` для затронутых путей.
4. Предпочитать удаление публичного доступа или перенос инструментов за пределы web root.
5. Ротировать учётные данные, если был раскрыт реальный секрет.
