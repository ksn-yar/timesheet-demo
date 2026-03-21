---
name: input-transformer
description: Правила создания Input Transformer в Infrastructure-слое — трансформация Request DTO (Infrastructure) в InputDto (Application) для Use Case, вынесение маппинга из контроллера
---

# Input Transformer: правила и практики

## Ключевые принципы

- **Input Transformer — инфраструктурный компонент**, расположенный в слое Infrastructure конкретного Bounded Context
- **Единственная ответственность**: трансформация Request DTO (Infrastructure) в InputDto (Application) для Use Case
- **`final class`** — не предназначен для наследования
- **Единственный публичный метод `transform(RequestDto): InputDto`** — явный, предсказуемый контракт
- **Не содержит бизнес-логики** — только маппинг полей (переименование, преобразование типов, вычисление значений из нескольких полей)
- **Контроллер остаётся тонким** — делегирует маппинг Transformer, а не выполняет его вручную

---

## Архитектурное расположение

```
src/
├── {BoundedContext}/
│   └── Infrastructure/
│       ├── Transformer/
│       │   └── CreateWorkEntryInputTransformer.php   # Transformer
│       ├── Controller/
│       │   └── CreateWorkEntryController.php         # использует Transformer
│       └── Dto/
│           └── CreateWorkEntryRequestDto.php         # исходный Request DTO
├── {BoundedContext}/
│   └── Application/
│       └── Dto/
│           └── CreateWorkEntryInputDto.php           # целевой InputDto
```

**Почему так:**
- Transformer зависит от Infrastructure DTO (Request DTO) и Application DTO (InputDto) — это инфраструктура
- Transformer привязан к HTTP-транспорту — размещается в `Infrastructure/Transformer/`
- Группировка Transformer, Controller, Dto внутри `Infrastructure/` обеспечивает когезию HTTP-слоя

---

## Правила именования

| Элемент     | Паттерн                                | Пример                            |
|-------------|----------------------------------------|-----------------------------------|
| Transformer | `{Action}{Entity}InputTransformer`     | `CreateWorkEntryInputTransformer` |
| Файл        | `{Action}{Entity}InputTransformer.php` | `CreateWorkEntryInputTransformer.php` |

**Действия (Action):**
- `Create` — создание (POST)
- `Update` — обновление (PUT/PATCH)
- `Delete` — удаление (DELETE)
- `Get` — получение одного (GET с path-параметром)
- `List` — получение списка (GET с фильтрами/пагинацией)

---

## 1. Шаблон Transformer

```php
// src/{BoundedContext}/Infrastructure/Transformer/{Action}{Entity}InputTransformer.php

declare(strict_types=1);

namespace App\{BoundedContext}\Infrastructure\Transformer;

use App\{BoundedContext}\Application\Dto\{Action}{Entity}InputDto;
use App\{BoundedContext}\Infrastructure\Dto\{Action}{Entity}RequestDto;

/** Трансформирует {Action}{Entity}RequestDto в {Action}{Entity}InputDto для Use Case. */
final class {Action}{Entity}InputTransformer
{
    public function transform({Action}{Entity}RequestDto $dto): {Action}{Entity}InputDto
    {
        return new {Action}{Entity}InputDto(
            // маппинг полей Request DTO -> InputDto
        );
    }
}
```

### Конкретный пример: создание записи рабочего времени

```php
// src/Timesheet/Infrastructure/Transformer/CreateWorkEntryInputTransformer.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\CreateWorkEntryInputDto;
use App\Timesheet\Infrastructure\Dto\CreateWorkEntryRequestDto;

/** Трансформирует CreateWorkEntryRequestDto в CreateWorkEntryInputDto для Use Case. */
final class CreateWorkEntryInputTransformer
{
    public function transform(CreateWorkEntryRequestDto $dto): CreateWorkEntryInputDto
    {
        return new CreateWorkEntryInputDto(
            employeeId: $dto->employeeId,
            startDate: $dto->startDate,
            endDate: $dto->endDate,
            hours: $dto->hours,
            description: $dto->comment,
        );
    }
}
```

### Конкретный пример: получение записи рабочего времени (GET с path-параметром)

Для GET-запроса с path-параметром Transformer принимает примитивный параметр (строку) и возвращает InputDto:

```php
// src/Timesheet/Infrastructure/Transformer/GetWorkEntryInputTransformer.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\GetWorkEntryInputDto;

/** Трансформирует path-параметр id в GetWorkEntryInputDto для Use Case. */
final class GetWorkEntryInputTransformer
{
    public function transform(string $id): GetWorkEntryInputDto
    {
        return new GetWorkEntryInputDto(id: $id);
    }
}
```

**Обратите внимание:** метод `transform()` принимает примитив (`string $id`), а не Request DTO — это допустимо для GET-запросов, где данные приходят из path-параметра, а не из тела запроса.

---

### Пример с нетривиальной трансформацией

Когда маппинг включает переименование полей, преобразование типов или вычисление значений:

```php
// src/Timesheet/Infrastructure/Transformer/UpdateWorkEntryInputTransformer.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\UpdateWorkEntryInputDto;
use App\Timesheet\Infrastructure\Dto\UpdateWorkEntryRequestDto;

/** Трансформирует UpdateWorkEntryRequestDto в UpdateWorkEntryInputDto для Use Case. */
final class UpdateWorkEntryInputTransformer
{
    public function transform(string $id, UpdateWorkEntryRequestDto $dto): UpdateWorkEntryInputDto
    {
        return new UpdateWorkEntryInputDto(
            id: $id,
            startDate: $dto->startDate,
            endDate: $dto->endDate,
            hours: $dto->hours,
            description: $dto->comment,
        );
    }
}
```

**Обратите внимание:** метод `transform()` может принимать дополнительные аргументы (например, `$id` из path-параметра), если они необходимы для формирования InputDto, но недоступны в Request DTO.

---

## 2. Использование в контроллере

Контроллер внедряет Transformer через конструктор и делегирует ему маппинг.

### Command-контроллер (POST/PUT/PATCH)

```php
// src/Timesheet/Infrastructure/Controller/CreateWorkEntryController.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Controller;

use App\Timesheet\Application\UseCase\CreateWorkEntryUseCase;
use App\Timesheet\Infrastructure\Dto\CreateWorkEntryRequestDto;
use App\Timesheet\Infrastructure\Transformer\CreateWorkEntryInputTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер создания записи рабочего времени. */
#[Route('/api/timesheet/work-entries', name: 'timesheet_create_work_entry', methods: ['POST'])]
final class CreateWorkEntryController extends AbstractController
{
    public function __construct(
        private readonly CreateWorkEntryUseCase $useCase,
        private readonly CreateWorkEntryInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateWorkEntryRequestDto::class)] CreateWorkEntryRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
```

### Query-контроллер (GET с path-параметром)

```php
// src/Timesheet/Infrastructure/Controller/GetWorkEntryController.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Controller;

use App\Timesheet\Application\UseCase\GetWorkEntryUseCase;
use App\Timesheet\Infrastructure\Presenter\HttpGetWorkEntryPresenter;
use App\Timesheet\Infrastructure\Transformer\GetWorkEntryInputTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения записи рабочего времени по идентификатору. */
#[Route('/api/timesheet/work-entries/{id}', name: 'timesheet_get_work_entry', methods: ['GET'])]
final class GetWorkEntryController extends AbstractController
{
    public function __construct(
        private readonly GetWorkEntryUseCase $useCase,
        private readonly HttpGetWorkEntryPresenter $presenter,
        private readonly GetWorkEntryInputTransformer $transformer,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $this->useCase->execute($this->transformer->transform($id));

        return $this->presenter->getResponse();
    }
}
```

---

### Command-контроллер с path-параметром (PUT/PATCH)

```php
// src/Timesheet/Infrastructure/Controller/UpdateWorkEntryController.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Controller;

use App\Timesheet\Application\UseCase\UpdateWorkEntryUseCase;
use App\Timesheet\Infrastructure\Dto\UpdateWorkEntryRequestDto;
use App\Timesheet\Infrastructure\Transformer\UpdateWorkEntryInputTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер обновления записи рабочего времени. */
#[Route('/api/timesheet/work-entries/{id}', name: 'timesheet_update_work_entry', methods: ['PATCH'])]
final class UpdateWorkEntryController extends AbstractController
{
    public function __construct(
        private readonly UpdateWorkEntryUseCase $useCase,
        private readonly UpdateWorkEntryInputTransformer $transformer,
    ) {}

    public function __invoke(
        string $id,
        #[ValueResolver(UpdateWorkEntryRequestDto::class)] UpdateWorkEntryRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($id, $dto));

        return new JsonResponse(null, Response::HTTP_OK);
    }
}
```

---

## 3. Transformer обязателен всегда

Input Transformer используется для **всех** методов, которые передают данные в Use Case: POST, PUT, PATCH, а также GET-запросы с формированием InputDto из параметров запроса.

**Создание InputDto напрямую в контроллере запрещено** — даже при тривиальном маппинге 1:1. Transformer обеспечивает:
- явное место для маппинга, которое легко найти и протестировать
- возможность добавить логику маппинга без изменения контроллера
- единый стиль — контроллер всегда делегирует трансформацию

Transformer может быть минимальным при идентичных полях:

```php
final class CreateWorkEntryInputTransformer
{
    public function transform(CreateWorkEntryRequestDto $dto): CreateWorkEntryInputDto
    {
        return new CreateWorkEntryInputDto(
            employeeId: $dto->employeeId,
            startDate: $dto->startDate,
            endDate: $dto->endDate,
            hours: $dto->hours,
        );
    }
}
```

---

## 4. Transformer с зависимостями

Если для трансформации необходимы внешние данные (например, текущий пользователь из Security), зависимости внедряются через конструктор:

```php
// src/Timesheet/Infrastructure/Transformer/CreateWorkEntryInputTransformer.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\CreateWorkEntryInputDto;
use App\Timesheet\Infrastructure\Dto\CreateWorkEntryRequestDto;
use Symfony\Bundle\SecurityBundle\Security;

/** Трансформирует CreateWorkEntryRequestDto в CreateWorkEntryInputDto, добавляя идентификатор текущего пользователя. */
final class CreateWorkEntryInputTransformer
{
    public function __construct(
        private readonly Security $security,
    ) {}

    public function transform(CreateWorkEntryRequestDto $dto): CreateWorkEntryInputDto
    {
        return new CreateWorkEntryInputDto(
            employeeId: $this->security->getUser()->getUserIdentifier(),
            startDate: $dto->startDate,
            endDate: $dto->endDate,
            hours: $dto->hours,
            description: $dto->comment,
        );
    }
}
```

**Важно:** Transformer с зависимостями от внешних сервисов — допустимый вариант для обогащения InputDto данными контекста. Но Transformer не должен обращаться к репозиториям для загрузки доменных объектов — это ответственность Use Case.

---

## 5. DI и автосвязывание

Transformer — обычный сервис, autowire/autoconfigure регистрируют его автоматически. Дополнительная конфигурация в `services.php` **не требуется**.

Если Transformer зависит от других сервисов — они внедряются через конструктор стандартным autowire.

---

## 6. Unit-тест Transformer

```php
// tests/Unit/{BoundedContext}/Infrastructure/Transformer/{Action}{Entity}InputTransformerTest.php

declare(strict_types=1);

namespace App\Tests\Unit\{BoundedContext}\Infrastructure\Transformer;

use App\{BoundedContext}\Application\Dto\{Action}{Entity}InputDto;
use App\{BoundedContext}\Infrastructure\Dto\{Action}{Entity}RequestDto;
use App\{BoundedContext}\Infrastructure\Transformer\{Action}{Entity}InputTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет трансформацию {Action}{Entity}RequestDto в {Action}{Entity}InputDto. */
final class {Action}{Entity}InputTransformerTest extends TestCase
{
    #[Test]
    public function transformsRequestDtoToInputDto(): void
    {
        $transformer = new {Action}{Entity}InputTransformer();
        $requestDto = new {Action}{Entity}RequestDto(
            // тестовые данные
        );

        $inputDto = $transformer->transform($requestDto);

        self::assertInstanceOf({Action}{Entity}InputDto::class, $inputDto);
        self::assertSame($requestDto->someField, $inputDto->someField);
        // проверка каждого поля
    }
}
```

### Конкретный пример

```php
// tests/Unit/Timesheet/Infrastructure/Transformer/CreateWorkEntryInputTransformerTest.php

declare(strict_types=1);

namespace App\Tests\Unit\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\CreateWorkEntryInputDto;
use App\Timesheet\Infrastructure\Dto\CreateWorkEntryRequestDto;
use App\Timesheet\Infrastructure\Transformer\CreateWorkEntryInputTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет трансформацию CreateWorkEntryRequestDto в CreateWorkEntryInputDto. */
final class CreateWorkEntryInputTransformerTest extends TestCase
{
    #[Test]
    public function transformsRequestDtoToInputDto(): void
    {
        $transformer = new CreateWorkEntryInputTransformer();
        $requestDto = new CreateWorkEntryRequestDto(
            employeeId: '550e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-01',
            endDate: '2026-03-15',
            hours: 8.0,
            comment: 'Работа над проектом X',
        );

        $inputDto = $transformer->transform($requestDto);

        self::assertInstanceOf(CreateWorkEntryInputDto::class, $inputDto);
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $inputDto->employeeId);
        self::assertSame('2026-03-01', $inputDto->startDate);
        self::assertSame('2026-03-15', $inputDto->endDate);
        self::assertSame(8.0, $inputDto->hours);
        self::assertSame('Работа над проектом X', $inputDto->description);
    }

    #[Test]
    public function transformsRequestDtoWithNullComment(): void
    {
        $transformer = new CreateWorkEntryInputTransformer();
        $requestDto = new CreateWorkEntryRequestDto(
            employeeId: '550e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-01',
            endDate: '2026-03-15',
            hours: 8.0,
        );

        $inputDto = $transformer->transform($requestDto);

        self::assertNull($inputDto->description);
    }
}
```

---

## 7. Взаимодействие компонентов

```
HTTP Request
    |
    v
+----------------------------+
|  Value Resolver            |  Десериализация + Валидация -> Request DTO
+-------------+--------------+
              | Request DTO
              v
+----------------------------+
|  Controller::__invoke()    |  Делегирует маппинг Transformer
+-------------+--------------+
              | Request DTO
              v
+----------------------------+
|  Transformer::transform()  |  Request DTO -> InputDto
+-------------+--------------+
              | InputDto
              v
+----------------------------+
|  UseCase::execute()        |  Бизнес-логика (Application слой)
+----------------------------+
              |
              v
       HTTP Response
```

---

## Чек-лист при создании Input Transformer

- [ ] Transformer размещён в `src/{BoundedContext}/Infrastructure/Transformer/`
- [ ] Именование: `{Action}{Entity}InputTransformer`
- [ ] Класс объявлен как `final class`
- [ ] Единственный публичный метод `transform(RequestDto): InputDto`
- [ ] Не содержит бизнес-логики — только маппинг полей
- [ ] Не обращается к репозиториям для загрузки доменных объектов
- [ ] PHPDoc-комментарий описывает трансформацию
- [ ] Unit-тест проверяет каждое поле результата
- [ ] Unit-тест проверяет nullable поля

---

## Антипаттерны

```php
// -- Бизнес-логика в Transformer -- только маппинг полей
final class CreateWorkEntryInputTransformer
{
    public function transform(CreateWorkEntryRequestDto $dto): CreateWorkEntryInputDto
    {
        if ($dto->hours > 12) {
            throw new \InvalidArgumentException('Слишком много часов');
        }
        // Валидация и бизнес-правила принадлежат Domain Entity или Validator
    }
}

// -- Transformer обращается к репозиторию -- загрузка данных это ответственность Use Case
final class CreateWorkEntryInputTransformer
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $repository, // ЗАПРЕЩЕНО
    ) {}

    public function transform(CreateWorkEntryRequestDto $dto): CreateWorkEntryInputDto
    {
        $employee = $this->repository->findById($dto->employeeId);
        // Загрузка доменных объектов — ответственность Use Case, не Transformer
    }
}

// -- Создание InputDto напрямую в контроллере — запрещено всегда
final class CreateWorkEntryController extends AbstractController
{
    public function __invoke(
        #[ValueResolver(CreateWorkEntryRequestDto::class)] CreateWorkEntryRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute(new CreateWorkEntryInputDto(  // ЗАПРЕЩЕНО
            employeeId: $dto->employeeId,
            startDate: $dto->startDate,
            endDate: $dto->endDate,
            hours: $dto->hours,
            description: $dto->comment,
        ));
        // Всегда используйте Transformer: $this->useCase->execute($this->transformer->transform($dto))
    }
}

// -- Transformer в Application или Domain слое
// src/Timesheet/Application/Transformer/CreateWorkEntryInputTransformer.php — НЕПРАВИЛЬНО
// src/Timesheet/Domain/Transformer/CreateWorkEntryInputTransformer.php — НЕПРАВИЛЬНО
// src/Timesheet/Infrastructure/Transformer/CreateWorkEntryInputTransformer.php — ПРАВИЛЬНО

// -- Transformer с несколькими публичными методами -- нарушает SRP
final class WorkEntryInputTransformer
{
    public function transformCreate(CreateWorkEntryRequestDto $dto): CreateWorkEntryInputDto { ... }
    public function transformUpdate(UpdateWorkEntryRequestDto $dto): UpdateWorkEntryInputDto { ... }
    // Один Transformer — один метод transform() — один маппинг
}
```
