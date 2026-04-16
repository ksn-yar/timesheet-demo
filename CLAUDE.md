# Общие правила

## Программная реализация проекта
- Корневая папка программной реализации проекта находится в папке app.

## Документация проекта
- Корневая папка документации находится в папке docs.

## Environment

This project runs entirely in Docker. Never run `php`, `composer`, `bin/console` commands or any project-specific CLI commands directly.

Always prefix with:
```bash
docker exec -it corpo-ts-backend php ...
docker exec -it corpo-ts-backend composer ...
docker exec -it corpo-ts-backend php bin/console ...
```

## File Path Convention
Always reference files relative to project root.

✅ Correct: `docs/technical-specification.md`
❌ Wrong:   `/home/john/projects/myapp/docs/technical-specification.md`

## Язык

**Правило:** Вся документация, комментарии в коде и текстовые ответы — на русском языке.

Исключения:

- Имена переменных, классов, методов — на английском (по стандартам языка программирования)
- Технические термины, не имеющие устоявшегося перевода (Use Case, Value Object, Aggregate Root)
- Названия инструментов и библиотек (Doctrine, PHPUnit, Symfony)
