# Справочник graphify: транскрибация видео и аудио

Загружай этот файл, только когда `detect` сообщил об одном или нескольких файлах `video`. Корпус без видео никогда не читает этот файл.

### Шаг 2.5 — транскрибация видео/аудио файлов (только если обнаружены видеофайлы)

Полностью пропусти этот шаг, если `detect` вернул ноль файлов `video`.

Видео- и аудиофайлы нельзя читать напрямую. Сначала транскрибируй их в текст, а затем на Шаге 3 обращайся с транскриптами как с файлами документов.

**Стратегия:** прочитай god-узлы из `graphify-out/.graphify_detect.json` (или файл анализа, если он существует от предыдущего запуска). Ты уже языковая модель — сам напиши одно предложение-подсказку о предметной области на основе этих меток. Затем передай его Whisper как начальный промпт. Отдельный вызов API не нужен.

**Однако**, если в корпусе *только* видеофайлы и нет других документов/кода, используй универсальный запасной промпт: `"Use proper punctuation and paragraph breaks."`

**Шаг 1 — напиши промпт для Whisper сам.**

Прочитай метки верхних god-узлов из вывода detect или анализа, затем составь короткое предложение-подсказку о предметной области, например:

- Метки: `transformer, attention, encoder, decoder` → `"Machine learning research on transformer architectures and attention mechanisms. Use proper punctuation and paragraph breaks."`
- Метки: `kubernetes, deployment, pod, helm` → `"DevOps discussion about Kubernetes deployments and Helm charts. Use proper punctuation and paragraph breaks."`

**Экспортируй** его как `GRAPHIFY_WHISPER_PROMPT` (именно это имя читает транскрибатор — и он должен быть `export`нут, чтобы дочерний Python-процесс его увидел) для следующей команды.

**Шаг 2 — транскрибация:**

```bash
export GRAPHIFY_WHISPER_MODEL=base  # или любой --whisper-model, который передал пользователь (должен быть экспортирован)
export GRAPHIFY_WHISPER_PROMPT="<the one-sentence domain hint you composed in Step 1>"
$(cat graphify-out/.graphify_python) -c "
import json, os, sys
from pathlib import Path
from graphify.transcribe import transcribe_all

detect = json.loads(Path('graphify-out/.graphify_detect.json').read_text(encoding=\"utf-8\"))
video_files = detect.get('files', {}).get('video', [])
prompt = os.environ.get('GRAPHIFY_WHISPER_PROMPT', 'Use proper punctuation and paragraph breaks.')

transcript_paths = transcribe_all(video_files, initial_prompt=prompt)
# Записывай JSON из Python (НЕ через shell-редирект '>'): transcribe_all/Whisper
# печатает прогресс в stdout, что иначе испортило бы JSON-файл (#1392).
Path('graphify-out/.graphify_transcripts.json').write_text(json.dumps(transcript_paths, ensure_ascii=False), encoding=\"utf-8\")
print(f'Transcribed {len(transcript_paths)} file(s)', file=sys.stderr)
"
```

После транскрибации:
- Прочитай пути транскриптов из `graphify-out/.graphify_transcripts.json`
- Добавь их в список документов перед отправкой семантических субагентов на Шаге 3B
- Выведи, сколько создано транскриптов: `Transcribed N video file(s) -> treating as docs`
- Если транскрибация файла не удалась, выведи предупреждение и продолжи с остальными

**Модель Whisper:** по умолчанию `base`. Если пользователь передал `--whisper-model <name>`, выполни `export GRAPHIFY_WHISPER_MODEL=<name>` (переменную нужно именно экспортировать, а не просто присвоить) перед запуском команды выше.
