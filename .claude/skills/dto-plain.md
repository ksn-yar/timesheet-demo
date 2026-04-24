---
name: dto-plain
description: Правила создания Application-layer DTO (InputDto, OutputDto) — простые объекты переноса данных между слоями без атрибутов валидации и сериализации
---

# Application-layer DTO: InputDto и OutputDto

## Ключевые принципы

- **DTO — компонент Application-слоя**, расположенный в `Application/Dto/` конкретного Bounded Context
- **Только переносит данные** — не содержит бизнес-логики, доменных правил или побочных эффектов
- **`final readonly class`** — иммутабельный после создания, продвижение свойств через конструктор
- **Только примитивные типы** — `string`, `int`, `float`, `bool`, `array`, `null` — никаких Value Object, Entity, Symfony-зависимостей
- **Без атрибутов валидации** — это не Request DTO (см. skill `dto-request-response`)
- **Без атрибутов сериализации и OpenAPI** — это не Response DTO
- **Два типа**: InputDto (входные данные для Use Case) и OutputDto (выходные данные от Use Case)

> **Разграничение с Infrastructure DTO:**
> Request/Response DTO в `Infrastructure/Dto/` — транспортный слой (HTTP, атрибуты Symfony).
> InputDto/OutputDto в `Application/Dto/` — чистый Application-слой, независимый от транспорта.

---

## Архитектурное расположение

```
src/
├── {BoundedContext}/
│   └── Application/
│       ├── UseCase/
│       │   └── {Action}{Entity}UseCase.php
│       └── Dto/
│           ├── {Action}{Entity}InputDto.php    # входное DTO Use Case
│           └── {Action}{Entity}OutputDto.php   # выходное DTO (только для Query Use Case)
```

**Потоки данных:**

```
HTTP Request
    ↓
ValueResolver / InputTransformer        (Infrastructure)
    ↓  создаёт InputDto именованными аргументами
{Action}{Entity}InputDto                (Application)
    ↓  передаётся в UseCase::execute()
Use Case                                (Application)
    ↓  создаёт OutputDto именованными аргументами
{Action}{Entity}OutputDto              (Application)
    ↓  передаётся в OutputPort::present()
Presenter                              (Infrastructure)
    ↓  формирует JsonResponse
HTTP Response
```

**Почему так:**
- InputDto/OutputDto зависят только от скалярных типов PHP — нет зависимостей от Infrastructure
- Application-слой остаётся чистым и тестируемым без инфраструктурного контекста
- Presenter изолирует Application от формата HTTP-ответа

---

## Правила именования

| Тип       | Паттерн                     | Пример                       |
|-----------|-----------------------------|------------------------------|
| InputDto  | `{Action}{Entity}InputDto`  | `CreateWorkEntryInputDto`    |
| OutputDto | `{Action}{Entity}OutputDto` | `GetWorkEntryOutputDto`      |

**Действия (Action):**
- `Create` — создание
- `Update` — обновление
- `Delete` — удаление
- `Get` — получение одного объекта
- `List` — получение коллекции

**Когда создавать OutputDto:**
- Query Use Case (`Get`, `List`) — всегда нужен OutputDto
- Command Use Case (`Create`, `Update`, `Delete`) — OutputDto нужен только если Use Case возвращает данные (например, `id` созданной сущности)

---

## 1. InputDto

InputDto передаёт входные данные из Infrastructure-слоя в Use Case. Создаётся в Value Resolver или InputTransformer.

### Шаблон InputDto

```php
// src/{BoundedContext}/Application/Dto/{Action}{Entity}InputDto.php

declare(strict_types=1);

namespace App\{BoundedContext}\Application\Dto;

/** Входные данные Use Case {описание действия}. */
final readonly class {Action}{Entity}InputDto
{
    public function __construct(
        public string $requiredStringField,
        public int $requiredIntField,
        public float $requiredFloatField,
        public bool $requiredBoolField,
        public ?string $optionalField = null,
    ) {}
}
```

### Конкретный пример: создание записи рабочего времени

```php
// src/Timesheet/Application/Dto/CreateWorkEntryInputDto.php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Входные данные Use Case создания записи рабочего времени. */
final readonly class CreateWorkEntryInputDto
{
    public function __construct(
        public string $employeeId,
        public string $projectId,
        public string $startDate,
        public string $endDate,
        public float $hours,
        public ?string $comment = null,
    ) {}
}
```

### Конкретный пример: список с фильтрами

```php
// src/Timesheet/Application/Dto/ListWorkEntriesInputDto.php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Входные данные Use Case получения списка записей рабочего времени с фильтрацией и пагинацией. */
final readonly class ListWorkEntriesInputDto
{
    public function __construct(
        public ?string $employeeId = null,
        public ?string $projectId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
```

---

## !! ИМЕНОВАННЫЕ АРГУМЕНТЫ — ОБЯЗАТЕЛЬНОЕ ПРАВИЛО !!

**При создании InputDto и OutputDto ВСЕГДА использовать именованные аргументы.**

Это главное правило данного skill. Именованные аргументы делают код самодокументирующимся, защищают от ошибок при изменении порядка параметров и облегчают code review.

```php
// ✅ ПРАВИЛЬНО — именованные аргументы
new CreateWorkEntryInputDto(
    employeeId: $requestDto->employeeId,
    projectId: $requestDto->projectId,
    startDate: $requestDto->startDate,
    endDate: $requestDto->endDate,
    hours: $requestDto->hours,
    comment: $requestDto->comment,
)

// ❌ ЗАПРЕЩЕНО — позиционные аргументы
new CreateWorkEntryInputDto(
    $requestDto->employeeId,
    $requestDto->projectId,
    $requestDto->startDate,
    $requestDto->endDate,
    $requestDto->hours,
    $requestDto->comment,
)
```

### Создание InputDto в InputTransformer

```php
// src/Timesheet/Infrastructure/Transformer/CreateWorkEntryInputTransformer.php

final readonly class CreateWorkEntryInputTransformer implements InputTransformerInterface
{
    public function transform(CreateWorkEntryRequestDto $requestDto): CreateWorkEntryInputDto
    {
        // ✅ Именованные аргументы обязательны
        return new CreateWorkEntryInputDto(
            employeeId: $requestDto->employeeId,
            projectId: $requestDto->projectId,
            startDate: $requestDto->startDate,
            endDate: $requestDto->endDate,
            hours: $requestDto->hours,
            comment: $requestDto->comment,
        );
    }
}
```

### Создание InputDto в Value Resolver

```php
// src/Timesheet/Infrastructure/ValueResolver/CreateWorkEntryValueResolver.php

protected function createInputDto(CreateWorkEntryRequestDto $requestDto): CreateWorkEntryInputDto
{
    // ✅ Именованные аргументы обязательны
    return new CreateWorkEntryInputDto(
        employeeId: $requestDto->employeeId,
        projectId: $requestDto->projectId,
        startDate: $requestDto->startDate,
        endDate: $requestDto->endDate,
        hours: $requestDto->hours,
        comment: $requestDto->comment,
    );
}
```

---

## 2. OutputDto

OutputDto передаёт выходные данные от Use Case в Presenter. Создаётся внутри Use Case и передаётся через OutputPort.

### Шаблон OutputDto

```php
// src/{BoundedContext}/Application/Dto/{Action}{Entity}OutputDto.php

declare(strict_types=1);

namespace App\{BoundedContext}\Application\Dto;

/** Выходные данные Use Case {описание действия}. */
final readonly class {Action}{Entity}OutputDto
{
    public function __construct(
        public string $id,
        public string $someField,
        public string $createdAt,
        public ?string $optionalField = null,
    ) {}
}
```

### Конкретный пример: получение записи рабочего времени

```php
// src/Timesheet/Application/Dto/GetWorkEntryOutputDto.php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Выходные данные Use Case получения записи рабочего времени. */
final readonly class GetWorkEntryOutputDto
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public string $projectId,
        public string $startDate,
        public string $endDate,
        public float $hours,
        public string $status,
        public ?string $comment,
        public string $createdAt,
    ) {}
}
```

### Конкретный пример: список с пагинацией

```php
// src/Timesheet/Application/Dto/ListWorkEntriesOutputDto.php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Выходные данные Use Case получения списка записей рабочего времени. */
final readonly class ListWorkEntriesOutputDto
{
    /**
     * @param GetWorkEntryOutputDto[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
```

### Создание OutputDto в Use Case с именованными аргументами

```php
// src/Timesheet/Application/UseCase/GetWorkEntryUseCase.php

final class GetWorkEntryUseCase implements GetWorkEntryUseCaseInterface
{
    public function __construct(
        private readonly WorkEntryRepositoryInterface $repository,
        private readonly GetWorkEntryOutputPortInterface $outputPort,
    ) {}

    public function execute(GetWorkEntryInputDto $input): void
    {
        $entry = $this->repository->findByIdOrFail(
            WorkEntryId::fromString($input->id),
        );

        // ✅ Именованные аргументы обязательны
        $this->outputPort->present(new GetWorkEntryOutputDto(
            id: $entry->getId()->toString(),
            employeeId: $entry->getEmployeeId()->toString(),
            projectId: $entry->getProjectId()->toString(),
            startDate: $entry->getStartDate()->format('Y-m-d'),
            endDate: $entry->getEndDate()->format('Y-m-d'),
            hours: $entry->getHours(),
            status: $entry->getStatus()->value,
            comment: $entry->getComment(),
            createdAt: $entry->getCreatedAt()->format(DateTimeInterface::ATOM),
        ));
    }
}
```

### Создание OutputDto для списка в Use Case с именованными аргументами

```php
// src/Timesheet/Application/UseCase/ListWorkEntriesUseCase.php

public function execute(ListWorkEntriesInputDto $input): void
{
    $result = $this->repository->findByFilter(
        employeeId: $input->employeeId !== null ? EmployeeId::fromString($input->employeeId) : null,
        page: $input->page,
        perPage: $input->perPage,
    );

    $items = array_map(
        fn(WorkEntry $entry): GetWorkEntryOutputDto => new GetWorkEntryOutputDto(
            // ✅ Именованные аргументы обязательны
            id: $entry->getId()->toString(),
            employeeId: $entry->getEmployeeId()->toString(),
            projectId: $entry->getProjectId()->toString(),
            startDate: $entry->getStartDate()->format('Y-m-d'),
            endDate: $entry->getEndDate()->format('Y-m-d'),
            hours: $entry->getHours(),
            status: $entry->getStatus()->value,
            comment: $entry->getComment(),
            createdAt: $entry->getCreatedAt()->format(DateTimeInterface::ATOM),
        ),
        $result->items,
    );

    // ✅ Именованные аргументы обязательны
    $this->outputPort->present(new ListWorkEntriesOutputDto(
        items: $items,
        total: $result->total,
        page: $input->page,
        perPage: $input->perPage,
    ));
}
```

---

## 3. Unit-тесты DTO

Тесты проверяют, что конструктор корректно инициализирует поля и что поля доступны только для чтения.

### Тест InputDto

```php
// tests/Unit/Timesheet/Application/Dto/CreateWorkEntryInputDtoTest.php

declare(strict_types=1);

namespace App\Tests\Unit\Timesheet\Application\Dto;

use App\Timesheet\Application\Dto\CreateWorkEntryInputDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет инициализацию InputDto создания записи рабочего времени. */
final class CreateWorkEntryInputDtoTest extends TestCase
{
    #[Test]
    public function storesAllFieldsCorrectly(): void
    {
        // ✅ В тестах также именованные аргументы
        $dto = new CreateWorkEntryInputDto(
            employeeId: '550e8400-e29b-41d4-a716-446655440000',
            projectId: '660e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-01',
            endDate: '2026-03-15',
            hours: 80.0,
            comment: 'Работа над проектом X',
        );

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $dto->employeeId);
        self::assertSame('660e8400-e29b-41d4-a716-446655440000', $dto->projectId);
        self::assertSame('2026-03-01', $dto->startDate);
        self::assertSame('2026-03-15', $dto->endDate);
        self::assertSame(80.0, $dto->hours);
        self::assertSame('Работа над проектом X', $dto->comment);
    }

    #[Test]
    public function optionalCommentDefaultsToNull(): void
    {
        $dto = new CreateWorkEntryInputDto(
            employeeId: '550e8400-e29b-41d4-a716-446655440000',
            projectId: '660e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-01',
            endDate: '2026-03-15',
            hours: 8.0,
        );

        self::assertNull($dto->comment);
    }
}
```

### Тест OutputDto

```php
// tests/Unit/Timesheet/Application/Dto/GetWorkEntryOutputDtoTest.php

declare(strict_types=1);

namespace App\Tests\Unit\Timesheet\Application\Dto;

use App\Timesheet\Application\Dto\GetWorkEntryOutputDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет инициализацию OutputDto получения записи рабочего времени. */
final class GetWorkEntryOutputDtoTest extends TestCase
{
    #[Test]
    public function storesAllFieldsCorrectly(): void
    {
        // ✅ В тестах также именованные аргументы
        $dto = new GetWorkEntryOutputDto(
            id: '550e8400-e29b-41d4-a716-446655440000',
            employeeId: '660e8400-e29b-41d4-a716-446655440000',
            projectId: '770e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-01',
            endDate: '2026-03-15',
            hours: 80.0,
            status: 'draft',
            comment: null,
            createdAt: '2026-03-01T00:00:00+00:00',
        );

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $dto->id);
        self::assertSame(80.0, $dto->hours);
        self::assertSame('draft', $dto->status);
        self::assertNull($dto->comment);
    }
}
```

---

## Антипаттерны

```php
// ❌ Позиционные аргументы при создании — ЗАПРЕЩЕНО
new CreateWorkEntryInputDto(
    $requestDto->employeeId,
    $requestDto->projectId,
    $requestDto->startDate,
    $requestDto->endDate,
    $requestDto->hours,
)
// Используйте именованные аргументы — это главное правило


// ❌ Value Object в Application DTO — нарушает изоляцию слоёв
final readonly class CreateWorkEntryInputDto
{
    public function __construct(
        public EmployeeId $employeeId, // ← VO из Domain-слоя
        public WorkEntryDate $startDate, // ← VO из Domain-слоя
    ) {}
}
// InputDto/OutputDto должны содержать только примитивные типы: string, int, float, bool, array, null


// ❌ Symfony-зависимости в Application DTO — нарушает Clean Architecture
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateWorkEntryInputDto
{
    public function __construct(
        #[Assert\NotBlank] // ← атрибуты валидации — только в Request DTO (Infrastructure)
        public string $employeeId,
    ) {}
}
// Валидация входных данных — ответственность Request DTO и Value Resolver


// ❌ Doctrine Entity в OutputDto — нарушает изоляцию Application от Persistence
final readonly class GetWorkEntryOutputDto
{
    public function __construct(
        public WorkEntry $entry, // ← Doctrine Entity из Infrastructure
    ) {}
}
// OutputDto должен содержать только примитивные данные, извлечённые из Entity в Use Case


// ❌ Мутабельный DTO — нарушает иммутабельность
class CreateWorkEntryInputDto
{
    public string $employeeId;
    public function setEmployeeId(string $id): void { $this->employeeId = $id; }
}
// Используйте final readonly class с constructor promotion


// ❌ Создание OutputDto в Presenter — нарушает ответственность слоёв
final class HttpGetWorkEntryPresenter
{
    public function present(WorkEntry $entry): void // ← Entity вместо OutputDto
    {
        $outputDto = new GetWorkEntryOutputDto( // OutputDto создаётся здесь — неправильно
            id: $entry->getId()->toString(),
            ...
        );
    }
}
// OutputDto создаётся в Use Case, Presenter только читает его поля


// ❌ Бизнес-логика в DTO
final readonly class CreateWorkEntryInputDto
{
    public function getTotalDays(): int
    {
        return (new DateTimeImmutable($this->endDate))
            ->diff(new DateTimeImmutable($this->startDate))
            ->days; // логика принадлежит доменному слою
    }
}
// DTO только переносит данные — никакой логики


// ❌ Один DTO для InputDto и OutputDto
final readonly class WorkEntryDto // используется и как вход, и как выход
// Разные контексты — разные DTO: InputDto для входа, OutputDto для выхода
```

---

## Чек-лист при создании DTO

### InputDto

- [ ] DTO размещён в `src/{BoundedContext}/Application/Dto/`
- [ ] Класс объявлен как `final readonly class`
- [ ] Свойства продвигаются через конструктор (constructor promotion)
- [ ] Только примитивные типы: `string`, `int`, `float`, `bool`, `array`, `null`
- [ ] Нет атрибутов валидации (`#[Assert\...]`)
- [ ] Нет Symfony-зависимостей
- [ ] Нет Value Object и Entity из Domain-слоя
- [ ] Необязательные поля nullable с `= null`
- [ ] PHPDoc-комментарий описывает назначение DTO
- [ ] InputDto создаётся с именованными аргументами в InputTransformer/ValueResolver
- [ ] Unit-тест проверяет корректность инициализации полей

### OutputDto

- [ ] DTO размещён в `src/{BoundedContext}/Application/Dto/`
- [ ] Класс объявлен как `final readonly class`
- [ ] Свойства продвигаются через конструктор (constructor promotion)
- [ ] Только примитивные типы: `string`, `int`, `float`, `bool`, `array`, `null`
- [ ] Нет атрибутов сериализации и OpenAPI (`#[OA\...]`)
- [ ] Нет Symfony-зависимостей
- [ ] Нет Value Object и Entity из Domain-слоя
- [ ] PHPDoc-комментарий описывает назначение DTO
- [ ] OutputDto создаётся с именованными аргументами в Use Case
- [ ] Unit-тест проверяет корректность инициализации полей
