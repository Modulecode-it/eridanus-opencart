# Стек: OpenCart 3.0.x

Готовый адаптер стека для OpenCart-проектов. Скопировать как `.agent/STACK.md` и адаптировать под конкретную версию/модули.

## Стек и Версия

- Язык: PHP (версия сервера).
- Фреймворк: OpenCart 3.0.x (укажите точный минорник, например 3.0.3.2).
- Шаблонизатор: Twig.
- БД: MySQL/MariaDB.
- Модификации: OCMOD XML.

## Структура Исходников

Краткая карта. Полная — в `PROJECT_STRUCTURE.md`.

```text
admin/        админское приложение
catalog/      storefront приложение
system/       ядро OpenCart, библиотеки, storage, OCMOD XML-файлы
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

1. Определить затронутое расширение (например: CDEK → `cdek_integrator`, `shipping/cdek`, `total/cdek`; Measoft → `shipping/measoftcourier`; и т.д.).
2. Проверить catalog- и admin-стороны расширения.
3. Проверить изменения статуса заказа, totals, расчёт shipping quote и логирование внешнего API.
4. По возможности протестировать полный сценарий checkout/order.
5. Ясно указать, если внешние API-вызовы не тестировались.

## Проверка

1. `php -l <file>` для изменённых PHP-файлов.
2. Проверка XML-синтаксиса при правках `.ocmod.xml`.
3. Для изменений витрины — ручная проверка затронутого catalog route.
4. Для изменений админки — права, языковые ключи, route контроллера, переменные шаблона.
5. Для изменений checkout/order/payment/shipping — полный поток заказа или явное указание, что не проверялось.

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
- `<custom-app>/config.php` — внешние `APP_ID`/`APP_PASSWORD` (например `yaorder/config.php`).
- `<custom-app>/*.token` — token files.
- Корневые админ-скрипты (`adminer-*.php`, `phpinfo.php`, `user.php`, и т.п.) — рассматривать как критичные; ротация/удаление публичного доступа по необходимости.
- `<backup>/logs/` — большие логи, вероятно operational data.

## Очистка Безопасности (если задача про безопасность)

1. Определить публичные service-файлы в корне (`user.php`, `phpinfo.php`, `adminer-*.php`, и т.п.).
2. Определить token/config-файлы (`config.php`, `admin/config.php`, `<custom-app>/config.php`, `*.token`).
3. Проверить deny rules в `.htaccess` для затронутых путей.
4. Предпочитать удаление публичного доступа или перенос инструментов за пределы web root.
5. Ротировать учётные данные, если был раскрыт реальный секрет.
