---
name: dto
description: Правила создания DTO (Data Transfer Object) — Request DTO для входящих HTTP-запросов и Response DTO для исходящих ответов API, с валидацией, сериализацией и OpenAPI-описанием
---

# DTO (Data Transfer Object): правила и практики

## Ключевые принципы

- **DTO — инфраструктурный компонент**, расположенный в слое Infrastructure конкретного Bounded Context
- **Только переносит данные** — не содержит бизнес-логики, доменных правил или побочных эффектов
- **`final readonly class`** — иммутабельный после создания, продвижение свойств через конструктор
- **Два типа DTO**: Request DTO (входящие данные из HTTP-запроса) и Response DTO (исходящие данные в HTTP-ответ)
- **Request DTO** содержит атрибуты валидации `Symfony\Component\Validator\Constraints`
- **Response DTO** содержит атрибуты сериализации `Symfony\Component\Serializer\Attribute` и OpenAPI-схему
- **Не реализует маркерных интерфейсов** — это простой data-класс

---

## Архитектурное расположение

```
src/
├── {BoundedContext}/
│   └── Infrastructure/
│       ├── Dto/
│       │   ├── CreateWorkEntryRequestDto.php     # Request DTO (входящие данные)
│       │   ├── UpdateWorkEntryRequestDto.php     # Request DTO
│       │   ├── WorkEntryResponseDto.php          # Response DTO (исходящие данные)
│       │   └── WorkEntryListResponseDto.php      # Response DTO (коллекция)
│       ├── ValueResolver/
│       │   └── CreateWorkEntryValueResolver.php  # десериализует Request DTO
│       └── Controller/
│           └── CreateWorkEntryController.php     # использует оба типа DTO
```

**Почему так:**
- DTO зависит от атрибутов Symfony (Validator, Serializer, OpenAPI) — это инфраструктура
- DTO привязан к HTTP-транспорту — размещается в `Infrastructure/Dto/`
- Группировка Dto, ValueResolver и Controller внутри `Infrastructure/` обеспечивает когезию HTTP-слоя

---

## Правила именования

| Тип DTO                    | Паттерн                      | Пример                      |
|----------------------------|------------------------------|-----------------------------|
| Request DTO (создание)     | `{Action}{Entity}RequestDto` | `CreateWorkEntryRequestDto` |
| Request DTO (обновление)   | `{Action}{Entity}RequestDto` | `UpdateWorkEntryRequestDto` |
| Request DTO (фильтрация)   | `{Action}{Entity}RequestDto` | `ListWorkEntriesRequestDto` |
| Response DTO (один объект) | `{Entity}ResponseDto`        | `WorkEntryResponseDto`      |
| Response DTO (коллекция)   | `{Entity}ListResponseDto`    | `WorkEntryListResponseDto`  |

**Действия (Action):**
- `Create` — создание (POST)
- `Update` — обновление (PUT/PATCH)
- `Delete` — удаление (DELETE)
- `Get` — получение одного (GET)
- `List` — получение списка (GET с фильтрами/пагинацией)

---

## 1. Request DTO

Request DTO принимает данные из HTTP-запроса. Десериализуется через Value Resolver (см. skill `value-resolver`), валидируется через атрибуты Symfony Validator.

### Шаблон Request DTO

```php
// src/{BoundedContext}/Infrastructure/Dto/{Action}{Entity}RequestDto.php

declare(strict_types=1);

namespace App\{BoundedContext}\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на {описание действия}. */
final readonly class {Action}{Entity}RequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Описание ошибки на русском.')]
        #[Assert\Uuid(message: 'Описание ошибки на русском.')]
        public string $fieldName,

        #[Assert\NotBlank(message: 'Описание ошибки на русском.')]
        #[Assert\Length(max: 255, maxMessage: 'Описание ошибки на русском.')]
        public string $anotherField,

        // Nullable поля для необязательных данных
        #[Assert\Length(max: 1000, maxMessage: 'Описание ошибки на русском.')]
        public ?string $optionalField = null,
    ) {}
}
```

### Конкретный пример: создание записи рабочего времени

```php
// src/Timesheet/Infrastructure/Dto/CreateWorkEntryRequestDto.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание записи рабочего времени. */
final readonly class CreateWorkEntryRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Идентификатор сотрудника обязателен.')]
        #[Assert\Uuid(message: 'Идентификатор сотрудника должен быть валидным UUID.')]
        public string $employeeId,

        #[Assert\NotBlank(message: 'Дата начала обязательна.')]
        #[Assert\Date(message: 'Дата начала должна быть в формате YYYY-MM-DD.')]
        public string $startDate,

        #[Assert\NotBlank(message: 'Дата окончания обязательна.')]
        #[Assert\Date(message: 'Дата окончания должна быть в формате YYYY-MM-DD.')]
        public string $endDate,

        #[Assert\NotBlank(message: 'Количество часов обязательно.')]
        #[Assert\Positive(message: 'Количество часов должно быть положительным числом.')]
        public float $hours,

        #[Assert\Length(max: 500, maxMessage: 'Комментарий не должен превышать 500 символов.')]
        public ?string $comment = null,
    ) {}
}
```

### Пример: Request DTO для обновления (PATCH — частичное обновление)

```php
// src/Timesheet/Infrastructure/Dto/UpdateWorkEntryRequestDto.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на обновление записи рабочего времени. */
final readonly class UpdateWorkEntryRequestDto
{
    public function __construct(
        #[Assert\Date(message: 'Дата начала должна быть в формате YYYY-MM-DD.')]
        public ?string $startDate = null,

        #[Assert\Date(message: 'Дата окончания должна быть в формате YYYY-MM-DD.')]
        public ?string $endDate = null,

        #[Assert\Positive(message: 'Количество часов должно быть положительным числом.')]
        public ?float $hours = null,

        #[Assert\Length(max: 500, maxMessage: 'Комментарий не должен превышать 500 символов.')]
        public ?string $comment = null,
    ) {}
}
```

### Пример: Request DTO для фильтрации/пагинации (GET-запрос)

```php
// src/Timesheet/Infrastructure/Dto/ListWorkEntriesRequestDto.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на получение списка записей рабочего времени с фильтрацией. */
final readonly class ListWorkEntriesRequestDto
{
    public function __construct(
        #[Assert\Uuid(message: 'Идентификатор сотрудника должен быть валидным UUID.')]
        public ?string $employeeId = null,

        #[Assert\Date(message: 'Дата начала фильтра должна быть в формате YYYY-MM-DD.')]
        public ?string $dateFrom = null,

        #[Assert\Date(message: 'Дата окончания фильтра должна быть в формате YYYY-MM-DD.')]
        public ?string $dateTo = null,

        #[Assert\Positive(message: 'Номер страницы должен быть положительным числом.')]
        public int $page = 1,

        #[Assert\Range(
            min: 1,
            max: 100,
            notInRangeMessage: 'Количество элементов на странице должно быть от {{ min }} до {{ max }}.',
        )]
        public int $perPage = 20,
    ) {}
}
```

**Десериализация GET-запроса:** для GET-запросов Value Resolver наследуется от `AbstractValueResolver` (не `AbstractJsonValueResolver`), и реализует `deserialize()` через query-параметры (см. skill `value-resolver`).

---

## 2. Response DTO

Response DTO формирует структуру исходящих данных API. Создаётся в контроллере или в Application-слое (Query Handler) и сериализуется в JSON через `Symfony\Component\Serializer\SerializerInterface` или `JsonResponse`.

### Шаблон Response DTO

```php
// src/{BoundedContext}/Infrastructure/Dto/{Entity}ResponseDto.php

declare(strict_types=1);

namespace App\{BoundedContext}\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными {сущности}. */
#[OA\Schema(
    schema: '{Entity}Response',
    description: 'Описание ответа на русском.',
)]
final readonly class {Entity}ResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
        public string $id,

        #[OA\Property(title: 'Название поля', description: 'Описание поля.', example: 'Пример значения')]
        public string $someField,

        #[OA\Property(title: 'Дата создания', description: 'Дата создания.', format: 'date-time', example: '2026-03-01T12:00:00+00:00')]
        public string $createdAt,
    ) {}

    /** Фабричный метод для создания из данных Persistence-слоя. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            someField: $data['someField'],
            createdAt: $data['createdAt'],
        );
    }
}
```

### Конкретный пример: Response DTO записи рабочего времени

```php
// src/Timesheet/Infrastructure/Dto/WorkEntryResponseDto.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными записи рабочего времени. */
#[OA\Schema(
    schema: 'WorkEntryResponse',
    description: 'Данные записи рабочего времени.',
)]
final readonly class WorkEntryResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор записи', description: 'Уникальный идентификатор записи.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
        public string $id,

        #[OA\Property(title: 'Идентификатор сотрудника', description: 'Идентификатор сотрудника.', format: 'uuid', example: '660e8400-e29b-41d4-a716-446655440000')]
        public string $employeeId,

        #[OA\Property(title: 'Дата начала', description: 'Дата начала периода.', format: 'date', example: '2026-03-01')]
        public string $startDate,

        #[OA\Property(title: 'Дата окончания', description: 'Дата окончания периода.', format: 'date', example: '2026-03-15')]
        public string $endDate,

        #[OA\Property(title: 'Количество часов', description: 'Количество часов.', example: 8.0)]
        public float $hours,

        #[OA\Property(title: 'Статус записи', description: 'Статус записи.', example: 'draft')]
        public string $status,

        #[OA\Property(title: 'Комментарий', description: 'Комментарий.', nullable: true, example: 'Работа над проектом X')]
        public ?string $comment,

        #[OA\Property(title: 'Дата создания', description: 'Дата создания.', format: 'date-time', example: '2026-03-01T12:00:00+00:00')]
        public string $createdAt,
    ) {}

    /** Фабричный метод для создания из массива данных Persistence-слоя. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            employeeId: $data['employeeId'],
            startDate: $data['startDate'],
            endDate: $data['endDate'],
            hours: (float) $data['hours'],
            status: $data['status'],
            comment: $data['comment'] ?? null,
            createdAt: $data['createdAt'],
        );
    }
}
```

### Пример: Response DTO для коллекции с пагинацией

```php
// src/Timesheet/Infrastructure/Dto/WorkEntryListResponseDto.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком записей рабочего времени и метаданными пагинации. */
#[OA\Schema(
    schema: 'WorkEntryListResponse',
    description: 'Список записей рабочего времени с пагинацией.',
)]
final readonly class WorkEntryListResponseDto
{
    /**
     * @param WorkEntryResponseDto[] $items
     */
    public function __construct(
        /** @var WorkEntryResponseDto[] */
        #[OA\Property(
            title: 'Записи',
            description: 'Список записей рабочего времени.',
            type: 'array',
            items: new OA\Items(ref: WorkEntryResponseDto::class),
        )]
        public array $items,

        #[OA\Property(title: 'Всего записей', description: 'Общее количество записей.', example: 42)]
        public int $total,

        #[OA\Property(title: 'Страница', description: 'Текущая страница.', example: 1)]
        public int $page,

        #[OA\Property(title: 'Размер страницы', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
```

---

## 3. Использование в контроллере

### Контроллер с Request DTO и Response DTO

```php
// src/Timesheet/Infrastructure/Controller/GetWorkEntryController.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Controller;

use App\Timesheet\Infrastructure\Dto\WorkEntryResponseDto;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения записи рабочего времени по идентификатору. */
#[OA\Tag(name: 'Timesheet')]
#[OA\Get(summary: 'Получить запись рабочего времени по ID')]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
)]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Запись рабочего времени найдена.',
    content: new OA\JsonContent(ref: WorkEntryResponseDto::class),
)]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Запись не найдена.')]
#[Route('/api/timesheet/work-entries/{id}', name: 'timesheet_get_work_entry', methods: ['GET'])]
final class GetWorkEntryController extends AbstractController
{
    public function __invoke(string $id): JsonResponse
    {
        // $result = $this->queryBus->ask(new GetWorkEntryQuery(id: $id));

        // Пример формирования Response DTO:
        // $responseDto = WorkEntryResponseDto::fromArray($result);
        // return $this->json($responseDto);

        return new JsonResponse(null, Response::HTTP_OK);
    }
}
```

---

## 4. Фабричные методы

Фабричные методы используются для создания DTO из разных источников данных. Они делают преобразование явным и тестируемым.

### Когда использовать фабричные методы

| Метод                            | Назначение                                    | Где размещается |
|----------------------------------|-----------------------------------------------|-----------------|
| `fromArray(array $data)`         | Создание из массива (результат Query Handler) | Response DTO    |
| `fromEntity(SomeEntity $entity)` | Создание из Doctrine Entity (через ACL)       | Response DTO    |

**Когда НЕ нужны фабричные методы:**
- Request DTO — десериализуется через Value Resolver автоматически, фабричные методы не нужны
- Если у DTO один источник данных и маппинг тривиален — конструктора достаточно

```php
// Response DTO с фабричными методами
final readonly class WorkEntryResponseDto
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public float $hours,
        public string $status,
    ) {}

    /** Создание из массива данных Query Handler. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            employeeId: $data['employeeId'],
            hours: (float) $data['hours'],
            status: $data['status'],
        );
    }

    /** Создание коллекции из массива записей. */
    public static function collection(array $items): array
    {
        return array_map(static fn(array $item): self => self::fromArray($item), $items);
    }
}
```

---

## 5. Справочник часто используемых атрибутов валидации

| Атрибут                    | Назначение                                  | Пример                         |
|----------------------------|---------------------------------------------|--------------------------------|
| `#[Assert\NotBlank]`       | Поле обязательно и не пустое                | Все обязательные поля          |
| `#[Assert\NotNull]`        | Поле не null (но может быть пустой строкой) | Флаги, пустые строки допустимы |
| `#[Assert\Uuid]`           | UUID формат                                 | Идентификаторы                 |
| `#[Assert\Email]`          | Валидный email                              | Email-поля                     |
| `#[Assert\Length]`         | Ограничение длины строки                    | Текстовые поля                 |
| `#[Assert\Range]`          | Числовой диапазон                           | Пагинация, количества          |
| `#[Assert\Positive]`       | Положительное число                         | Часы, суммы                    |
| `#[Assert\PositiveOrZero]` | Неотрицательное число                       | Скидки, бонусы                 |
| `#[Assert\Date]`           | Формат YYYY-MM-DD                           | Даты                           |
| `#[Assert\DateTime]`       | Формат YYYY-MM-DD HH:MM:SS                  | Даты с временем                |
| `#[Assert\Choice]`         | Значение из допустимого списка              | Статусы, типы                  |
| `#[Assert\Type]`           | Проверка типа данных                        | Массивы, числа                 |
| `#[Assert\Valid]`          | Каскадная валидация вложенного объекта      | Вложенные DTO                  |
| `#[Assert\All]`            | Валидация каждого элемента массива          | Коллекции                      |
| `#[Assert\Count]`          | Ограничение количества элементов            | Массивы                        |
| `#[Assert\Regex]`          | Соответствие регулярному выражению          | Специальные форматы            |

---

## 6. Вложенные DTO

Когда запрос содержит вложенные объекты, используются вложенные DTO с каскадной валидацией.

```php
// src/Timesheet/Infrastructure/Dto/CreateTimesheetRequestDto.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание табеля с записями. */
final readonly class CreateTimesheetRequestDto
{
    /**
     * @param WorkEntryItemDto[] $entries
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Идентификатор сотрудника обязателен.')]
        #[Assert\Uuid(message: 'Идентификатор сотрудника должен быть валидным UUID.')]
        public string $employeeId,

        #[Assert\NotBlank(message: 'Месяц обязателен.')]
        #[Assert\Regex(pattern: '/^\d{4}-\d{2}$/', message: 'Месяц должен быть в формате YYYY-MM.')]
        public string $month,

        /** @var WorkEntryItemDto[] */
        #[Assert\NotBlank(message: 'Список записей не может быть пустым.')]
        #[Assert\Count(min: 1, minMessage: 'Должна быть хотя бы одна запись.')]
        #[Assert\Valid]
        public array $entries,
    ) {}
}
```

```php
// src/Timesheet/Infrastructure/Dto/WorkEntryItemDto.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO элемента записи рабочего времени во вложенной структуре запроса. */
final readonly class WorkEntryItemDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Дата обязательна.')]
        #[Assert\Date(message: 'Дата должна быть в формате YYYY-MM-DD.')]
        public string $date,

        #[Assert\NotBlank(message: 'Количество часов обязательно.')]
        #[Assert\Positive(message: 'Количество часов должно быть положительным числом.')]
        public float $hours,

        #[Assert\Length(max: 255, maxMessage: 'Описание не должно превышать 255 символов.')]
        public ?string $description = null,
    ) {}
}
```

**Важно:** атрибут `#[Assert\Valid]` на массиве `$entries` запускает каскадную валидацию каждого `WorkEntryItemDto`. Без него вложенные объекты не валидируются.

---

## 7. Unit-тесты DTO

### Тест Request DTO (проверка валидации)

```php
// tests/Unit/{BoundedContext}/Infrastructure/Dto/CreateWorkEntryRequestDtoTest.php

declare(strict_types=1);

namespace App\Tests\Unit\{BoundedContext}\Infrastructure\Dto;

use App\{BoundedContext}\Infrastructure\Dto\CreateWorkEntryRequestDto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** Проверяет правила валидации Request DTO на создание записи рабочего времени. */
final class CreateWorkEntryRequestDtoTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    #[Test]
    public function validDtoPassesValidation(): void
    {
        $dto = new CreateWorkEntryRequestDto(
            employeeId: '550e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-01',
            endDate: '2026-03-15',
            hours: 8.0,
        );

        $errors = $this->validator->validate($dto);

        self::assertCount(0, $errors);
    }

    #[Test]
    #[DataProvider('invalidDataProvider')]
    public function invalidDtoFailsValidation(
        string $employeeId,
        string $startDate,
        string $endDate,
        float $hours,
        int $expectedErrorCount,
    ): void {
        $dto = new CreateWorkEntryRequestDto(
            employeeId: $employeeId,
            startDate: $startDate,
            endDate: $endDate,
            hours: $hours,
        );

        $errors = $this->validator->validate($dto);

        self::assertGreaterThanOrEqual($expectedErrorCount, $errors->count());
    }

    public static function invalidDataProvider(): iterable
    {
        yield 'пустой employeeId' => ['', '2026-03-01', '2026-03-15', 8.0, 1];
        yield 'невалидный UUID' => ['not-a-uuid', '2026-03-01', '2026-03-15', 8.0, 1];
        yield 'невалидная дата начала' => ['550e8400-e29b-41d4-a716-446655440000', 'not-a-date', '2026-03-15', 8.0, 1];
        yield 'отрицательные часы' => ['550e8400-e29b-41d4-a716-446655440000', '2026-03-01', '2026-03-15', -1.0, 1];
        yield 'нулевые часы' => ['550e8400-e29b-41d4-a716-446655440000', '2026-03-01', '2026-03-15', 0.0, 1];
    }
}
```

### Тест Response DTO (проверка фабричного метода)

```php
// tests/Unit/{BoundedContext}/Infrastructure/Dto/WorkEntryResponseDtoTest.php

declare(strict_types=1);

namespace App\Tests\Unit\{BoundedContext}\Infrastructure\Dto;

use App\{BoundedContext}\Infrastructure\Dto\WorkEntryResponseDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет фабричные методы Response DTO записи рабочего времени. */
final class WorkEntryResponseDtoTest extends TestCase
{
    #[Test]
    public function createsFromArray(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'employeeId' => '660e8400-e29b-41d4-a716-446655440000',
            'startDate' => '2026-03-01',
            'endDate' => '2026-03-15',
            'hours' => '80.00',
            'status' => 'draft',
            'comment' => null,
            'createdAt' => '2026-03-01T00:00:00+00:00',
        ];

        $dto = WorkEntryResponseDto::fromArray($data);

        self::assertSame($data['id'], $dto->id);
        self::assertSame($data['employeeId'], $dto->employeeId);
        self::assertSame(80.0, $dto->hours);
        self::assertSame('draft', $dto->status);
        self::assertNull($dto->comment);
    }
}
```

---

## Чек-лист при создании DTO

### Request DTO

- [ ] DTO размещён в `src/{BoundedContext}/Infrastructure/Dto/`
- [ ] Класс объявлен как `final readonly class`
- [ ] Свойства продвигаются через конструктор (constructor promotion)
- [ ] Все обязательные поля имеют `#[Assert\NotBlank]`
- [ ] UUID-поля имеют `#[Assert\Uuid]`
- [ ] Даты имеют `#[Assert\Date]` или `#[Assert\DateTime]`
- [ ] Строковые поля имеют `#[Assert\Length]` с `max`
- [ ] Числовые поля имеют `#[Assert\Positive]` / `#[Assert\Range]` по необходимости
- [ ] Необязательные поля nullable с значением по умолчанию `null`
- [ ] Сообщения валидации на русском языке
- [ ] Вложенные DTO массивы имеют `#[Assert\Valid]` для каскадной валидации
- [ ] PHPDoc-комментарий на классе описывает назначение DTO
- [ ] Парный Value Resolver создан (см. skill `value-resolver`)
- [ ] Unit-тест проверяет валидацию (валидные и невалидные данные)

### Response DTO

- [ ] DTO размещён в `src/{BoundedContext}/Infrastructure/Dto/`
- [ ] Класс объявлен как `final readonly class`
- [ ] Свойства продвигаются через конструктор (constructor promotion)
- [ ] Атрибут `#[OA\Schema]` на классе с описанием
- [ ] Атрибуты `#[OA\Property]` на каждом свойстве с `title`, `description` и `example`
- [ ] Форматы указаны: `format: 'uuid'`, `format: 'date'`, `format: 'date-time'`
- [ ] Nullable свойства помечены `nullable: true` в `#[OA\Property]`
- [ ] Фабричный метод `fromArray()` для создания из массива данных
- [ ] PHPDoc-комментарий на классе описывает назначение DTO
- [ ] Unit-тест проверяет фабричные методы

---

## Антипаттерны

```php
// ❌ Мутабельный DTO — нарушает иммутабельность
class CreateWorkEntryRequestDto
{
    public string $employeeId;
    public function setEmployeeId(string $id): void { $this->employeeId = $id; }
}
// Используйте final readonly class с конструктором

// ❌ Бизнес-логика в DTO — DTO только переносит данные
final readonly class CreateWorkEntryRequestDto
{
    public function calculateTotalHours(): float
    {
        return $this->hours * $this->daysCount; // логика принадлежит домену
    }
}

// ❌ Доменные Value Objects в инфраструктурном DTO — нарушает слоистость
final readonly class CreateWorkEntryRequestDto
{
    public function __construct(
        public EmployeeId $employeeId, // ← VO из домена, DTO не должен знать о нём
    ) {}
}
// Используйте примитивные типы: string, int, float, bool, array

// ❌ DTO без атрибутов валидации — данные не проверяются
final readonly class CreateWorkEntryRequestDto
{
    public function __construct(
        public string $employeeId, // нет Assert\NotBlank, Assert\Uuid
    ) {}
}
// Всегда добавляйте атрибуты валидации к Request DTO

// ❌ Response DTO без OpenAPI-атрибутов — API не документировано
final readonly class WorkEntryResponseDto
{
    public function __construct(
        public string $id, // нет OA\Property — не появится в Swagger UI
    ) {}
}

// ❌ OA\Property без title и example — Swagger UI показывает безымянное поле без примера
#[OA\Property(description: 'Идентификатор записи.', format: 'uuid')]
public string $id,
// Всегда указывайте title и example:
#[OA\Property(title: 'Идентификатор записи', description: 'Идентификатор записи.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
public string $id,

// ❌ Один DTO для запроса и ответа — нарушает SRP
final readonly class WorkEntryDto
{
    // Используется и для десериализации запроса, и для сериализации ответа.
    // Разные контексты — разные DTO: RequestDto и ResponseDto
}

// ❌ Использование fromRequest() вместо Value Resolver
final readonly class CreateWorkEntryRequestDto
{
    public static function fromRequest(Request $request): self
    {
        $data = json_decode($request->getContent(), true);
        return new self(...$data);
        // Десериализация — ответственность Value Resolver, не DTO
    }
}

// ❌ DTO в доменном слое
// src/Timesheet/Domain/Dto/WorkEntryDto.php — НЕПРАВИЛЬНО
// src/Timesheet/Infrastructure/Dto/WorkEntryResponseDto.php — ПРАВИЛЬНО

// ❌ Request DTO с сообщениями валидации на английском
#[Assert\NotBlank(message: 'Employee ID is required.')]
// Сообщения валидации пишутся на русском языке (согласно CLAUDE.md)
```
