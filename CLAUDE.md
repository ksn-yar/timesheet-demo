# Общие правила

## Программная реализация проекта 
- Корневая папка Backend реализации проекта находится в папке appBackend.
- Корневая папка Frontend реализации проекта находится в папке appFrontend.

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

## Temporary Files

Never use `/tmp`, `/var/tmp` (system-level) or any absolute path outside the project root.
Use `./tmp/` (project-relative) for all temporary files if necessary.

## Project Memory

All common persistent notes, decisions and context are stored in `./memory/`.

## PHP-классы: readonly по умолчанию

**Правило:** Все PHP-классы должны быть объявлены как `readonly` по умолчанию.

Исключения (не использовать `readonly`):
- Классы, которые явно требуют мутабельного состояния (указывать причину в комментарии)

## By DDD layer:
| Layer           | readonly? | Reason                     |
|-----------------|-----------|----------------------------|
| DTO             | ✅ always  | Data transfer, no mutation |
| Value Object    | ✅ always  | Immutability is the point  |
| Command / Query | ✅ always  | Input objects              |
| Event           | ✅ always  | Facts, never mutated       |
| Entity          | ❌ usually | Lifecycle state changes    |
| Repository      | ❌         | Service with dependencies  |
| Service         | ❌         | Stateless but not readonly |

```php
// ✅ Правильно — Value Object, DTO, сервис
readonly class CreateUserCommand { ... }

// ✅ Правильно — Doctrine Entity (исключение)
#[ORM\Entity]
class User { ... }

// ❌ Неправильно — забыли readonly
class CreateUserCommand { ... }
```

## Язык

**Правило:** Вся документация, комментарии в коде и текстовые ответы — на русском языке.

Исключения:

- Имена переменных, классов, методов — на английском (по стандартам языка программирования)
- Технические термины, не имеющие устоявшегося перевода (Use Case, Value Object, Aggregate Root)
- Названия инструментов и библиотек (Doctrine, PHPUnit, Symfony)
