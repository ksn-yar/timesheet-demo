# Общие правила

## File Path Convention
Always reference files relative to project root.

✅ Correct: `src/Domain/User/Entity/User.php`
❌ Wrong:   `/home/john/projects/myapp/src/Domain/User/Entity/User.php`

## Язык

**Правило:** Вся документация, комментарии в коде и текстовые ответы — на русском языке.

Исключения:

- Имена переменных, классов, методов — на английском (по стандартам языка программирования)
- Технические термины, не имеющие устоявшегося перевода (Use Case, Value Object, Aggregate Root)
- Названия инструментов и библиотек (Doctrine, PHPUnit, Symfony)
