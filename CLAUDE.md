# Общие правила

## Программная реализация проекта
- Корневая папка программной реализации проекта находится в папке app.

## Документация проекта
- Корневая папка документации находится в папке docs.

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
