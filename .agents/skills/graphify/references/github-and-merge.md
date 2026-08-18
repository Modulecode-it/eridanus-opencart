# Справочник graphify: клонирование с GitHub и кросс-репозиторное слияние

Загружай этот файл, когда пользователь передал один или несколько URL `https://github.com/...` или назвал несколько локальных подпапок для слияния в один граф.

### Шаг 0 — клонирование репозиториев GitHub (только если передан URL GitHub)

**Один репозиторий:**
```bash
LOCAL_PATH=$(graphify clone <github-url> [--branch <branch>])
# Используй LOCAL_PATH как цель для всех последующих шагов
```

**Несколько репозиториев (кросс-репозиторный граф):**
```bash
# Склонируй каждый репозиторий, запусти на каждом полный пайплайн, затем слей
graphify clone <url1>   # → ~/.graphify/repos/<owner1>/<repo1>
graphify clone <url2>   # → ~/.graphify/repos/<owner2>/<repo2>
# Запусти /graphify на каждом локальном пути, чтобы получить их файлы graph.json
# Затем слияние:
graphify merge-graphs \
  ~/.graphify/repos/<owner1>/<repo1>/graphify-out/graph.json \
  ~/.graphify/repos/<owner2>/<repo2>/graphify-out/graph.json \
  --out graphify-out/cross-repo-graph.json
```

Graphify клонирует в `~/.graphify/repos/<owner>/<repo>` и повторно использует существующие клоны при повторных запусках. Каждый узел объединённого графа несёт атрибут `repo`, поэтому можно фильтровать по источнику.

**Несколько локальных подпапок (монорепозиторий или мульти-сервисная структура):**

Пайплайн скилла записывает все промежуточные и финальные результаты в `graphify-out/` в текущем рабочем каталоге. Запуск скилла на каждой подпапке отдельно затрёт один и тот же выходной каталог. Вместо этого используй CLI напрямую для каждой подпапки — он помещает `graphify-out/` *внутрь* сканируемого пути:

```bash
graphify extract ./core/     # → ./core/graphify-out/graph.json
graphify extract ./service/  # → ./service/graphify-out/graph.json
graphify extract ./platform/ # → ./platform/graphify-out/graph.json
# Добавь --backend gemini|kimi|openai|deepseek|claude-cli в зависимости от того, какой API-ключ у тебя настроен

# Затем слей в корне проекта:
graphify merge-graphs \
  ./core/graphify-out/graph.json \
  ./service/graphify-out/graph.json \
  ./platform/graphify-out/graph.json \
  --out graphify-out/graph.json
```

Как только `graphify-out/graph.json` существует, включается описанный выше быстрый путь: любой вопрос о кодовой базе запускает `graphify query` напрямую на объединённом графе — без повторного извлечения и без проверки размера.
