# Стек: Laravel

Готовый адаптер стека для Laravel-проектов. Скопировать как `.agent/STACK.md` и адаптировать под версию/пакеты конкретного проекта.

## Стек и Версия

- Язык: PHP 8.x (укажите минимальную версию, например 8.2+).
- Фреймворк: Laravel (укажите мажор, например 10.x / 11.x).
- Пакетный менеджер: Composer (backend), npm/pnpm/yarn/bun (frontend).
- БД: MySQL / PostgreSQL / SQLite (укажите).
- Очереди: Laravel Queue + (опционально) Horizon / Redis.
- Тесты: PHPUnit / Pest (укажите).
- Фронт: Blade / Inertia + Vue/React / Livewire / Volt (укажите).

## Структура Исходников

Стандартная структура Laravel. Полная карта — в `PROJECT_STRUCTURE.md`.

```text
app/
|-- Models/              Eloquent-модели
|-- Http/
|   |-- Controllers/     контроллеры (тонкие)
|   |-- Requests/        Form Requests (валидация)
|   |-- Middleware/      HTTP middleware
|   |-- Resources/       API-ресурсы (трансформация в JSON)
|-- Services/            доменные сервисы (бизнес-логика)
|-- Actions/             single-action классы (если используется паттерн)
|-- Jobs/                jobs для очередей
|-- Mail/                mailable-классы
|-- Notifications/       уведомления
|-- Policies/            авторизация
|-- Providers/           сервис-провайдеры
|-- Console/Commands/    artisan-команды
|-- Exceptions/          обработчики исключений

database/
|-- migrations/          миграции схемы (timestamp_name.php)
|-- seeders/             сидеры
|-- factories/           фабрики для тестов

routes/
|-- web.php              веб-маршруты
|-- api.php              API-маршруты
|-- console.php          консольные команды-замыкания
|-- channels.php         broadcasting-каналы

resources/views/         Blade-шаблоны
resources/js/            frontend (если Inertia/Vue/React)
resources/css/           стили
lang/ (или resources/lang/)  локализация
config/                  конфиги
tests/                   PHPUnit/Pest тесты
```

## Команды

| Действие | Команда |
|---|---|
| Поставить зависимости | `composer install` / `npm install` |
| Обновить зависимости | `composer update` / `npm update` |
| Запустить локально | `php artisan serve` (или Sail/Herd/Valet/Octane) |
| Миграции (применить) | `php artisan migrate` |
| Миграции (откатить) | `php artisan migrate:rollback` |
| Новая миграция | `php artisan make:migration <name>` |
| Список маршрутов | `php artisan route:list` |
| Тесты | `php artisan test` (или `vendor/bin/phpunit` / `vendor/bin/pest`) |
| Тесты (один файл) | `php artisan test --filter=<Name>` |
| Линт / стиль | `./vendor/bin/pint` (Laravel Pint) |
| Статический анализ | `./vendor/bin/phpstan analyse` (если установлен) |
| Тинкер (REPL) | `php artisan tinker` |
| Очистить кэш | `php artisan optimize:clear` |
| Запустить очереди | `php artisan queue:work` (или Horizon) |
| Сгенерировать ключ | `php artisan key:generate` |
| Сидеры | `php artisan db:seed` |

## Правила Изменений Laravel

1. **Тонкие контроллеры**: контроллер только принимает запрос, делегирует сервису/action, возвращает ответ. Бизнес-логика — в `app/Services/` или `app/Actions/`.
2. **Валидация — через Form Requests** (`app/Http/Requests/`), не в контроллере и не в модели.
3. **Eloquent предпочтительнее raw SQL**. Raw SQL/`DB::` — только когда Eloquent неэффективен (mass-update, сложные JOIN); обязательно параметризовать.
4. **Миграции должны быть обратимыми**: метод `up()` + `down()`. Не оставлять пустой `down()` без причины.
5. **Один migration = одно логическое изменение**. Не менять чужую миграцию — создайте новую.
6. **Никогда не редактировать `vendor/`** — это сторонний код.
7. **Конфиг в `.env`** и `config/*.php`, не хардкодить секреты. Чтение через `config(...)`, не `env(...)` (кроме конфиг-файлов).
8. **Авторизация — через Policies** (`app/Policies/`), не разбрасывать проверки по контроллерам.
9. **Большие задачи — в Queue/Jobs**, не выполнять синхронно в запросе (отправка почты, внешние API, тяжёлые вычисления).
10. **Маршруты именовать** (`->name('...')`) и использовать `route()` в коде, не хардкодить URL.
11. **Сообщения об ошибках и локализация** — через `lang/` (функции `__()`, `trans()`).
12. **БД-транзакции** для операций, меняющих несколько связанных записей: `DB::transaction(...)`.

## Изменение Маршрутов

1. Добавлять маршруты в `routes/web.php` (веб) или `routes/api.php` (API), не в обоих без причины.
2. Использовать группы для middleware и префиксов.
3. Называть маршруты (`->name('admin.users.index')`).
4. После изменений запускать `php artisan route:list` для проверки.
5. API-маршруты по умолчанию под `prefix: api` и без session-state (если не переопределено).

## Изменение БД И Миграций

1. Создавать новую миграцию через `php artisan make:migration`.
2. Имя миграции: `<действие>_<что>_<таблица>_table` (например `add_status_to_orders_table`).
3. Обязательно `down()` для отката.
4. После написания — `php artisan migrate` в dev-окружении для проверки.
5. Заполняемые данные — в `database/seeders/`, фабрики для тестов — в `database/factories/`.
6. Eloquent-модель: `$fillable` или `$guarded` для mass-assignment; касты в `$casts`.

## Изменение Blade / Frontend

1. Шаблоны — в `resources/views/`, организованы по доменам.
2. Переиспользуемые части — в components (`resources/views/components/` или `View::component`).
3. Для Inertia-проектов — логика в Vue/React-компонентах, данные через `Inertia::render(...)` с типизированными props.
4. Не делать запросы к БД в шаблонах — подготавливать данные в контроллере/сервисе.
5. Фронтенд-ассеты — через Vite (`npm run dev` / `npm run build`).

## Изменение API

1. Трансформация — через API Resources (`app/Http/Resources/`), не возвращать модели напрямую (если не REST-проект с согласованной конвенцией).
2. Валидация — Form Requests.
3. Авторизация — Policies + middleware (`can:...`).
4. Версионирование при необходимости (`routes/api.php` с группой `v1`).
5. Consistent JSON-структура ошибок (см. обработчик исключений в `bootstrap/app.php` / `app/Exceptions/Handler.php`).

## Проверка

1. **Стиль**: `./vendor/bin/pint` (автофикс) или `--test` (только проверить).
2. **Стат. анализ** (если установлен): `./vendor/bin/phpstan analyse`.
3. **Тесты**: `php artisan test` — для затронутой области `--filter=<Name>`.
4. **Маршруты**: `php artisan route:list` после изменений маршрутов/middleware.
5. **Миграции**: `php artisan migrate` в dev + проверка `migrate:rollback`.
6. **Очереди**: если меняете Job — протестировать через `php artisan queue:work` + `tinker` (`dispatch(new ...)`).
7. **Внешние API**: явно указать, если вызовы к платёжкам/доставке не тестировались.

## Пути, По Умолчанию Не Сканировать

- `vendor/` — сторонние пакеты Composer.
- `node_modules/` — фронтенд-зависимости.
- `storage/` — runtime-данные (logs, framework cache, sessions, uploads).
- `bootstrap/cache/` — скомпилированные конфиги/маршруты/сервисы.
- `public/build/`, `public/vendor/` — собранные ассеты.
- `.git/`, `.idea/`, `.vscode/`.
- `lang/vendor/`, `resources/js/vendor/` — сторонние переводы/фронтенд.

## Критичные Файлы И Секреты

- `.env` — содержит креды БД, ключи шифрования, токены внешних сервисов. **Никогда** не копировать дословно в документы; держать актуальный `.env.example` с плейсхолдерами.
- `config/*.php` — может содержать секреты, особенно `config/services.php`, `config/database.php`, `config/mail.php`.
- `storage/logs/laravel.log` — может содержать чувствительные данные; не публиковать.
- `database/seeders/` с реальными паролями — заменить на фабрики/плейсхолдеры.
- Ключи платёжек (Stripe, CloudPayments, YooKassa, и т.п.) — только в `.env`, нигде больше.

## Очистка Безопасности (если задача про безопасность)

1. Проверить, что `.env` не закоммичен (обычно в `.gitignore`).
2. Проверить, что в репо нет реальных секретов: `git log -p | grep -iE "(password|secret|api_key|token)"` с осторожностью.
3. Проверить `APP_DEBUG=false` в продакшене (раскрытие stack traces).
4. Проверить middleware на критичных маршрутах (`auth`, `verified`, `can:...`).
5. Mass-assignment: модели должны иметь `$fillable`/`$guarded`.
6. SQL-инъекции: raw-запросы должны использовать bindings (`DB::select('... WHERE id = ?', [$id])`).
7. Ротировать секреты, если был раскрыт реальный (включая `APP_KEY`).
