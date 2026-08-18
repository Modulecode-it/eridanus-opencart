---
name: git-sync
description: >
  Синхронизация с GitHub (pull/push) для этого репозитория. Используй когда пользователь
  просит забрать, подтянуть, отправить, спушить изменения, обновить код из GitHub,
  сделать git pull или git push, или любое упоминание синхронизации с удалённым репозиторием.
---

# Git Sync — синхронизация с GitHub

Этот репозиторий синхронизируется с GitHub по HTTPS с Personal Access Token для авторизации (аккаунт `Modulecode-it`).

> ℹ️ Remote `origin` в этом репо настроен по SSH (`git@github.com:Modulecode-it/eridanus-opencart.git`). Команды ниже используют **явный HTTPS-URL** репозитория, поэтому схема HTTPS + PAT работает независимо от настроенного remote. Ветка — `main`.

> 🔑 **PAT.** В командах ниже стоит плейсхолдер `<GITHUB_PAT>`. Реальный PAT аккаунта `Modulecode-it` вставь в **локальную копию** этого файла (3 вхождения) и **не коммить** его: GitHub Push Protection блокирует пуш с секретом. В репозитории токена быть не должно.

## Pull (забрать изменения)

Выполни в одном пайпе:

```bash
git stash && git -c credential.helper='!f() { echo "username=Modulecode-it"; echo "password=<GITHUB_PAT>"; }; f' pull https://github.com/Modulecode-it/eridanus-opencart.git main && git stash pop
```

### Если возник конфликт при stash pop

1. Прочитай конфликтующий файл
2. Если обе стороны идентичны или различаются минимально — возьми upstream версию:
   ```bash
   git checkout --theirs <file> && git add <file>
   ```
3. Удали stash: `git stash drop`
4. Покажи пользователю статус: `git status`

### Если локальных изменений нет

Выполни просто:

```bash
git -c credential.helper='!f() { echo "username=Modulecode-it"; echo "password=<GITHUB_PAT>"; }; f' pull https://github.com/Modulecode-it/eridanus-opencart.git main
```

## Push (отправить изменения)

Перед пушем спроси пользователя — нужно ли закоммитить локальные изменения.

Для пуша:

```bash
git -c credential.helper='!f() { echo "username=Modulecode-it"; echo "password=<GITHUB_PAT>"; }; f' push https://github.com/Modulecode-it/eridanus-opencart.git main
```

## Статус

```bash
git status && git log --oneline -5
```
