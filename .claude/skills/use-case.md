---
name: use-case
description: Правила создания Use Case в Application-слое — единственный сценарий использования системы, оркестрация доменных объектов, взаимодействие с Presenter через OutputPort
---

# Use Case: правила и практики

## Ключевые принципы

- **Use Case — единственный сценарий использования системы**: один класс — один сценарий (Single Responsibility Principle)
- **Расположен в Application слое** конкретного Bounded Context — оркестрирует доменные объекты
- **Зависит только от интерфейсов**: репозитории, сервисы, OutputPort — только через интерфейсы из Domain слоя и Application слоя
- **Не зависит от инфраструктуры**: никаких `use Symfony\...`, `use Doctrine\...`
- **Единственный публичный метод `execute(InputDto $input): void`** — не возвращает данные напрямую
- **Orchestrator**: не содержит бизнес-логики — делегирует доменным объектам и сервисам
- **Два типа Use Case**: Command (Create/Update/Delete — без Presenter) и Query (Get/List — с Presenter через OutputPort)

---

## Архитектурное расположение

```
src/
├── {BoundedContext}/
│   └── Application/
│       ├── UseCase/
│       │   └── {Action}{Entity}UseCase.php              # Use Case
│       ├── Dto/
│       │   ├── {Action}{Entity}InputDto.php             # входное DTO
│       │   └── {Action}{Entity}OutputDto.php            # выходное DTO (только для Query)
│       └── Port/
│           └── {Action}{Entity}OutputPortInterface.php  # интерфейс Presenter (только для Query)
```

**Почему так:**
- Use Case — Application-слой по Clean Architecture: оркестрирует Domain-объекты, не зависит от инфраструктуры
- InputDto и OutputDto находятся рядом с Use Case — обеспечивают когезию Application-слоя
- OutputPortInterface определяется в Application — Use Case задаёт контракт, инфраструктура реализует (Dependency Inversion Principle)

---

## Правила именования

| Элемент    | Паттерн                               | Пример                             |
|------------|---------------------------------------|------------------------------------|
| Use Case   | `{Action}{Entity}UseCase`             | `CreateWorkEntryUseCase`           |
| InputDto   | `{Action}{Entity}InputDto`            | `CreateWorkEntryInputDto`          |
| OutputDto  | `{Action}{Entity}OutputDto`           | `GetWorkEntryOutputDto`            |
| OutputPort | `{Action}{Entity}OutputPortInterface` | `GetWorkEntryOutputPortInterface`  |

**Действия (Action):**
- `Create` — создание
- `Update` — обновление
- `Delete` — удаление
- `Get` — получение одного
- `List` / `GetAll` — получение списка

---

## 1. InputDto (Application слой)

InputDto — входной объект Use Case. Содержит только примитивные типы и не зависит от инфраструктуры. Не содержит атрибутов валидации — валидация выполняется на уровне Infrastructure DTO (Request DTO) через Value Resolver (см. skill `value-resolver`).

### Шаблон InputDto

```php
// src/{BoundedContext}/Application/Dto/{Action}{Entity}InputDto.php

declare(strict_types=1);

namespace App\{BoundedContext}\Application\Dto;

/** Входные данные Use Case {описание действия}. */
final readonly class {Action}{Entity}InputDto
{
    public function __construct(
        public string $someField,
        public ?string $optionalField = null,
        // только примитивные типы: string, int, float, bool, array
    ) {}
}
```

### Конкретный пример

```php
// src/Timesheet/Application/Dto/CreateWorkEntryInputDto.php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Входные данные Use Case создания записи рабочего времени. */
final readonly class CreateWorkEntryInputDto
{
    public function __construct(
        public string $employeeId,
        public string $startDate,
        public string $endDate,
        public float $hours,
        public ?string $description = null,
    ) {}
}
```

### Правила для InputDto

- **`final readonly class`** — иммутабельный после создания
- **Свойства через constructor promotion** — только примитивные типы (`string`, `int`, `float`, `bool`, `array`)
- **Не зависит от инфраструктуры** — нет `use Symfony\...`, `use Doctrine\...`
- **Не содержит атрибутов валидации** — валидация выполняется в Infrastructure-слое (Request DTO)
- **Не содержит доменных Value Objects** — маппинг примитивов в Value Objects выполняется в Use Case

---

## 2. Use Case — Command (Create / Update / Delete)

Command Use Case выполняет изменяющее действие и не возвращает данных. Не использует OutputPort/Presenter.

### Шаблон Command Use Case

```php
// src/{BoundedContext}/Application/UseCase/{Action}{Entity}UseCase.php

declare(strict_types=1);

namespace App\{BoundedContext}\Application\UseCase;

use App\{BoundedContext}\Application\Dto\{Action}{Entity}InputDto;
use App\{BoundedContext}\Domain\Repository\{Entity}RepositoryInterface;

/** Use Case {описание действия}. */
final class {Action}{Entity}UseCase
{
    public function __construct(
        private readonly {Entity}RepositoryInterface $repository,
        // другие доменные сервисы/репозитории — только через интерфейсы
    ) {}

    public function execute({Action}{Entity}InputDto $input): void
    {
        // 1. Создать/получить доменный объект (маппинг примитивов -> Value Objects)
        // 2. Делегировать бизнес-логику доменному объекту
        // 3. Сохранить через репозиторий
    }
}
```

### Конкретный пример: создание записи рабочего времени

```php
// src/Timesheet/Application/UseCase/CreateWorkEntryUseCase.php

declare(strict_types=1);

namespace App\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\CreateWorkEntryInputDto;
use App\Timesheet\Domain\Entity\WorkEntry;
use App\Timesheet\Domain\Exception\EmployeeNotFoundException;
use App\Timesheet\Domain\Repository\EmployeeRepositoryInterface;
use App\Timesheet\Domain\Repository\WorkEntryRepositoryInterface;
use App\Timesheet\Domain\ValueObject\DateRange;
use App\Timesheet\Domain\ValueObject\EmployeeId;
use App\Timesheet\Domain\ValueObject\Hours;

/** Use Case создания записи рабочего времени для сотрудника. */
final class CreateWorkEntryUseCase
{
    public function __construct(
        private readonly WorkEntryRepositoryInterface $workEntryRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
    ) {}

    public function execute(CreateWorkEntryInputDto $input): void
    {
        $employeeId = new EmployeeId($input->employeeId);

        $employee = $this->employeeRepository->findById($employeeId);

        if ($employee === null) {
            throw new EmployeeNotFoundException($input->employeeId);
        }

        $workEntry = WorkEntry::create(
            employeeId: $employeeId,
            period: DateRange::fromStrings($input->startDate, $input->endDate),
            hours: new Hours($input->hours),
            description: $input->description,
        );

        $this->workEntryRepository->save($workEntry);
    }
}
```

### Конкретный пример: удаление записи рабочего времени

```php
// src/Timesheet/Application/UseCase/DeleteWorkEntryUseCase.php

declare(strict_types=1);

namespace App\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\DeleteWorkEntryInputDto;
use App\Timesheet\Domain\Exception\WorkEntryNotFoundException;
use App\Timesheet\Domain\Repository\WorkEntryRepositoryInterface;
use App\Timesheet\Domain\ValueObject\WorkEntryId;

/** Use Case удаления записи рабочего времени. */
final class DeleteWorkEntryUseCase
{
    public function __construct(
        private readonly WorkEntryRepositoryInterface $repository,
    ) {}

    public function execute(DeleteWorkEntryInputDto $input): void
    {
        $workEntry = $this->repository->findById(new WorkEntryId($input->id));

        if ($workEntry === null) {
            throw new WorkEntryNotFoundException($input->id);
        }

        $this->repository->remove($workEntry);
    }
}
```

---

## 3. Use Case — Query (Get / List)

Query Use Case получает данные и передаёт результат через OutputPort (Presenter). Не возвращает данные напрямую.

### Шаблон Query Use Case

```php
// src/{BoundedContext}/Application/UseCase/{Action}{Entity}UseCase.php

declare(strict_types=1);

namespace App\{BoundedContext}\Application\UseCase;

use App\{BoundedContext}\Application\Dto\{Action}{Entity}InputDto;
use App\{BoundedContext}\Application\Dto\{Action}{Entity}OutputDto;
use App\{BoundedContext}\Application\Port\{Action}{Entity}OutputPortInterface;
use App\{BoundedContext}\Domain\Repository\{Entity}RepositoryInterface;

/** Use Case {описание действия}. */
final class {Action}{Entity}UseCase
{
    public function __construct(
        private readonly {Entity}RepositoryInterface $repository,
        private readonly {Action}{Entity}OutputPortInterface $presenter,
    ) {}

    public function execute({Action}{Entity}InputDto $input): void
    {
        // 1. Получить доменный объект
        // 2. Маппинг Value Objects -> примитивы в OutputDto
        // 3. Передать данные в Presenter через OutputDto

        $this->presenter->present(new {Action}{Entity}OutputDto(
            // маппинг доменных объектов -> примитивы
        ));
    }
}
```

### Конкретный пример: получение записи рабочего времени

```php
// src/Timesheet/Application/UseCase/GetWorkEntryUseCase.php

declare(strict_types=1);

namespace App\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\GetWorkEntryInputDto;
use App\Timesheet\Application\Dto\GetWorkEntryOutputDto;
use App\Timesheet\Application\Port\GetWorkEntryOutputPortInterface;
use App\Timesheet\Domain\Exception\WorkEntryNotFoundException;
use App\Timesheet\Domain\Repository\WorkEntryRepositoryInterface;
use App\Timesheet\Domain\ValueObject\WorkEntryId;

/** Use Case получения записи рабочего времени по идентификатору. */
final class GetWorkEntryUseCase
{
    public function __construct(
        private readonly WorkEntryRepositoryInterface $repository,
        private readonly GetWorkEntryOutputPortInterface $presenter,
    ) {}

    public function execute(GetWorkEntryInputDto $input): void
    {
        $workEntry = $this->repository->findById(new WorkEntryId($input->id));

        if ($workEntry === null) {
            throw new WorkEntryNotFoundException($input->id);
        }

        $this->presenter->present(new GetWorkEntryOutputDto(
            id: $workEntry->getId()->value,
            employeeId: $workEntry->getEmployeeId()->value,
            startDate: $workEntry->getStartDate()->format('Y-m-d'),
            endDate: $workEntry->getEndDate()->format('Y-m-d'),
            description: $workEntry->getDescription(),
        ));
    }
}
```

---

## 4. Взаимодействие с другими компонентами

### Command Use Case (Create / Update / Delete)

```
HTTP Request
    |
    v
+----------------------------+
|  Controller::__invoke()    |  Делегирует маппинг Transformer, вызывает Use Case
+-------------+--------------+
              | Request DTO
              v
+----------------------------+
|  Transformer::transform()  |  Request DTO -> InputDto (см. skill input-transformer)
+-------------+--------------+
              | InputDto
              v
+----------------------------+
|  UseCase::execute()       |  Маппинг примитивов -> Value Objects
|  (Application слой)        |  Делегирует доменным объектам
+-------------+--------------+
              | Domain Entity
              v
+----------------------------+
|  Repository::save()        |  Сохранение через интерфейс
|  (Domain интерфейс)        |
+----------------------------+
              |
              v
       HTTP Response (201 Created / 204 No Content)
```

### Query Use Case (Get / List)

```
HTTP Request
    |
    v
+----------------------------+
|  Controller::__invoke()    |  Делегирует маппинг Transformer, вызывает Use Case
+-------------+--------------+
              | InputDto
              v
+----------------------------+
|  UseCase::execute()       |  Получает доменный объект
|  (Application слой)        |  Вызывает $presenter->present(OutputDto)
+-------------+--------------+
              | OutputDto
              v
+----------------------------+
|  Presenter::present()      |  Трансформирует OutputDto в JsonResponse
|  (Infrastructure слой)     |  Сохраняет результат в $this->response
+----------------------------+
              |
              v
+----------------------------+
|  Controller                |  Забирает $presenter->getResponse()
+----------------------------+
              |
              v
       HTTP Response (200 OK)
```

---

## 5. Использование в контроллере

### Контроллер с Command Use Case

Маппинг Request DTO -> InputDto выполняется через Input Transformer (см. skill `input-transformer`). Контроллер не создаёт InputDto вручную — делегирует это Transformer.

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

### Контроллер с Query Use Case

Маппинг параметров запроса -> InputDto выполняется через Input Transformer (см. skill `input-transformer`). Контроллер не создаёт InputDto вручную — делегирует это Transformer.

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

## 6. DI и autowire

Use Case — обычный сервис, автоматически регистрируется через autowire/autoconfigure.

### Зависимости Use Case

| Зависимость                     | Источник                    | Привязка                              |
|---------------------------------|-----------------------------|---------------------------------------|
| `{Entity}RepositoryInterface`   | Domain слой                 | alias в `services.php` или `#[AsAlias]` |
| `{Action}{Entity}OutputPortInterface` | Application слой      | alias в `services.php` или `#[AsAlias]` |
| Доменные сервисы (интерфейсы)   | Domain слой                 | alias в `services.php` или `#[AsAlias]` |

### Пример привязки

```php
// config/services.php

use App\Timesheet\Application\Port\GetWorkEntryOutputPortInterface;
use App\Timesheet\Domain\Repository\WorkEntryRepositoryInterface;
use App\Timesheet\Infrastructure\Presenter\HttpGetWorkEntryPresenter;
use App\Timesheet\Infrastructure\Repository\DoctrineWorkEntryRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(WorkEntryRepositoryInterface::class, DoctrineWorkEntryRepository::class);
    $services->alias(GetWorkEntryOutputPortInterface::class, HttpGetWorkEntryPresenter::class);
};
```

---

## 7. Обработка исключений

Use Case бросает **доменные исключения**. Инфраструктурный слой (ExceptionListener) преобразует их в HTTP-ответы.

### Правила

- Use Case **бросает** только доменные исключения из `Domain/Exception/`
- Use Case **не бросает** `HttpException`, `JsonException`, `\RuntimeException` и другие инфраструктурные исключения
- Доменные исключения — чистые PHP-классы, не зависят от Symfony

### Примеры доменных исключений

```php
// src/Timesheet/Domain/Exception/WorkEntryNotFoundException.php

declare(strict_types=1);

namespace App\Timesheet\Domain\Exception;

/** Запись рабочего времени не найдена по указанному идентификатору. */
final class WorkEntryNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Запись рабочего времени с ID "%s" не найдена.', $id));
    }
}
```

```php
// src/Timesheet/Domain/Exception/EmployeeNotFoundException.php

declare(strict_types=1);

namespace App\Timesheet\Domain\Exception;

/** Сотрудник не найден по указанному идентификатору. */
final class EmployeeNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Сотрудник с ID "%s" не найден.', $id));
    }
}
```

---

## 8. Unit-тесты Use Case

### Тест Command Use Case

```php
// tests/Unit/Timesheet/Application/UseCase/CreateWorkEntryUseCaseTest.php

declare(strict_types=1);

namespace App\Tests\Unit\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\CreateWorkEntryInputDto;
use App\Timesheet\Application\UseCase\CreateWorkEntryUseCase;
use App\Timesheet\Domain\Entity\Employee;
use App\Timesheet\Domain\Entity\WorkEntry;
use App\Timesheet\Domain\Exception\EmployeeNotFoundException;
use App\Timesheet\Domain\Repository\EmployeeRepositoryInterface;
use App\Timesheet\Domain\Repository\WorkEntryRepositoryInterface;
use App\Timesheet\Domain\ValueObject\EmployeeId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет сценарий создания записи рабочего времени. */
final class CreateWorkEntryUseCaseTest extends TestCase
{
    private WorkEntryRepositoryInterface $workEntryRepository;
    private EmployeeRepositoryInterface $employeeRepository;
    private CreateWorkEntryUseCase $useCase;

    protected function setUp(): void
    {
        $this->workEntryRepository = $this->createMock(WorkEntryRepositoryInterface::class);
        $this->employeeRepository = $this->createMock(EmployeeRepositoryInterface::class);
        $this->useCase = new CreateWorkEntryUseCase(
            $this->workEntryRepository,
            $this->employeeRepository,
        );
    }

    #[Test]
    public function createsWorkEntryForExistingEmployee(): void
    {
        $employee = $this->createMock(Employee::class);

        $this->employeeRepository
            ->method('findById')
            ->willReturn($employee);

        $this->workEntryRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(WorkEntry::class));

        $this->useCase->execute(new CreateWorkEntryInputDto(
            employeeId: '550e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-01',
            endDate: '2026-03-15',
            hours: 8.0,
            description: 'Работа над проектом',
        ));
    }

    #[Test]
    public function throwsExceptionWhenEmployeeNotFound(): void
    {
        $this->employeeRepository
            ->method('findById')
            ->willReturn(null);

        $this->workEntryRepository
            ->expects(self::never())
            ->method('save');

        $this->expectException(EmployeeNotFoundException::class);

        $this->useCase->execute(new CreateWorkEntryInputDto(
            employeeId: '550e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-01',
            endDate: '2026-03-15',
            hours: 8.0,
        ));
    }
}
```

### Тест Query Use Case

```php
// tests/Unit/Timesheet/Application/UseCase/GetWorkEntryUseCaseTest.php

declare(strict_types=1);

namespace App\Tests\Unit\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\GetWorkEntryInputDto;
use App\Timesheet\Application\Dto\GetWorkEntryOutputDto;
use App\Timesheet\Application\Port\GetWorkEntryOutputPortInterface;
use App\Timesheet\Application\UseCase\GetWorkEntryUseCase;
use App\Timesheet\Domain\Entity\WorkEntry;
use App\Timesheet\Domain\Exception\WorkEntryNotFoundException;
use App\Timesheet\Domain\Repository\WorkEntryRepositoryInterface;
use App\Timesheet\Domain\ValueObject\WorkEntryId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет сценарий получения записи рабочего времени. */
final class GetWorkEntryUseCaseTest extends TestCase
{
    private WorkEntryRepositoryInterface $repository;
    private GetWorkEntryOutputPortInterface $presenter;
    private GetWorkEntryUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WorkEntryRepositoryInterface::class);
        $this->presenter = $this->createMock(GetWorkEntryOutputPortInterface::class);
        $this->useCase = new GetWorkEntryUseCase($this->repository, $this->presenter);
    }

    #[Test]
    public function presentsWorkEntryWhenFound(): void
    {
        $workEntry = $this->createConfiguredMock(WorkEntry::class, [
            'getId' => new WorkEntryId('550e8400-e29b-41d4-a716-446655440000'),
        ]);

        $this->repository
            ->method('findById')
            ->willReturn($workEntry);

        $this->presenter
            ->expects(self::once())
            ->method('present')
            ->with(self::isInstanceOf(GetWorkEntryOutputDto::class));

        $this->useCase->execute(new GetWorkEntryInputDto(
            id: '550e8400-e29b-41d4-a716-446655440000',
        ));
    }

    #[Test]
    public function throwsExceptionWhenWorkEntryNotFound(): void
    {
        $this->repository
            ->method('findById')
            ->willReturn(null);

        $this->presenter
            ->expects(self::never())
            ->method('present');

        $this->expectException(WorkEntryNotFoundException::class);

        $this->useCase->execute(new GetWorkEntryInputDto(
            id: '550e8400-e29b-41d4-a716-446655440000',
        ));
    }
}
```

---

## Чек-лист при создании Use Case

### InputDto (Application)

- [ ] InputDto размещён в `src/{BoundedContext}/Application/Dto/`
- [ ] Класс объявлен как `final readonly class`
- [ ] Свойства продвигаются через конструктор (constructor promotion)
- [ ] Только примитивные типы (`string`, `int`, `float`, `bool`, `array`)
- [ ] Нет зависимостей от инфраструктуры (`Symfony`, `Doctrine`)
- [ ] Нет атрибутов валидации — валидация в Infrastructure DTO (Request DTO)
- [ ] PHPDoc-комментарий на классе описывает назначение

### Use Case (Application)

- [ ] Use Case размещён в `src/{BoundedContext}/Application/UseCase/`
- [ ] Класс объявлен как `final class`
- [ ] Единственный публичный метод `execute(InputDto): void`
- [ ] Зависит только от интерфейсов из Domain слоя и Application слоя
- [ ] Нет `use Symfony\...`, `use Doctrine\...`
- [ ] Не содержит бизнес-логики — делегирует доменным объектам
- [ ] Маппинг примитивов в Value Objects выполняется в Use Case
- [ ] Бросает только доменные исключения

### Command Use Case (Create / Update / Delete)

- [ ] Не использует OutputPortInterface — просто сохраняет/удаляет
- [ ] Контроллер возвращает HTTP-ответ напрямую (201 Created, 204 No Content)

### Query Use Case (Get / List)

- [ ] Зависит от OutputPortInterface (интерфейс из Application/Port)
- [ ] Передаёт результат через `$this->presenter->present(OutputDto)`
- [ ] OutputDto содержит только примитивные типы (маппинг Value Objects -> примитивы в Use Case)

### DI-контейнер

- [ ] Use Case автоматически регистрируется через autowire
- [ ] Интерфейсы зависимостей привязаны к реализациям (alias в `services.php` или `#[AsAlias]`)

### Тесты

- [ ] Unit-тест проверяет успешный сценарий (объект создан/найден/удалён)
- [ ] Unit-тест проверяет доменные исключения (сущность не найдена)
- [ ] Unit-тест проверяет вызов `present()` с корректным OutputDto (для Query)
- [ ] Unit-тест проверяет, что `present()` не вызывается при ошибке (для Query)
- [ ] Unit-тест проверяет, что `save()` не вызывается при ошибке (для Command)

---

## Антипаттерны

```php
// -- Use Case возвращает данные напрямую -- нарушает Presenter-паттерн
final class GetWorkEntryUseCase
{
    public function execute(GetWorkEntryInputDto $input): GetWorkEntryOutputDto
    {
        // return $dto; // Use Case НЕ ДОЛЖЕН возвращать данные
        // Используйте $this->presenter->present($dto)
    }
}

// -- Use Case содержит бизнес-логику -- должно быть в Domain
final class CreateWorkEntryUseCase
{
    public function execute(CreateWorkEntryInputDto $input): void
    {
        if ($input->hours > 12) {
            throw new \InvalidArgumentException('Слишком много часов');
        }
        // Валидация бизнес-правил принадлежит Domain Entity или Domain Service
    }
}

// -- Use Case зависит от инфраструктуры
use Symfony\Component\HttpFoundation\JsonResponse;

final class GetWorkEntryUseCase
{
    public function execute(GetWorkEntryInputDto $input): JsonResponse
    {
        // Use Case не должен знать о JsonResponse — это ответственность Presenter
    }
}

// -- Use Case бросает инфраструктурное исключение
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetWorkEntryUseCase
{
    public function execute(GetWorkEntryInputDto $input): void
    {
        throw new NotFoundHttpException('Not found'); // НЕПРАВИЛЬНО
        // Используйте доменное исключение: throw new WorkEntryNotFoundException($input->id)
    }
}

// -- Несколько сценариев в одном Use Case -- нарушает SRP
final class WorkEntryUseCase
{
    public function create(CreateWorkEntryInputDto $input): void { ... }
    public function update(UpdateWorkEntryInputDto $input): void { ... }
    public function delete(DeleteWorkEntryInputDto $input): void { ... }
    // Один Use Case — один сценарий — один метод execute()
}

// -- Use Case обращается к Doctrine EntityManager напрямую
use Doctrine\ORM\EntityManagerInterface;

final class CreateWorkEntryUseCase
{
    public function __construct(
        private readonly EntityManagerInterface $em, // ЗАПРЕЩЕНО
    ) {}
    // Используйте доменный интерфейс репозитория
}

// -- InputDto с доменными Value Objects
final readonly class CreateWorkEntryInputDto
{
    public function __construct(
        public EmployeeId $employeeId, // VO из домена — используйте string
    ) {}
}
// InputDto оперирует примитивными типами, маппинг в VO выполняется в Use Case

// -- Use Case в Infrastructure слое
// src/Timesheet/Infrastructure/UseCase/CreateWorkEntryUseCase.php — НЕПРАВИЛЬНО
// src/Timesheet/Application/UseCase/CreateWorkEntryUseCase.php — ПРАВИЛЬНО

// -- Метод Use Case не execute() — всегда используйте execute()
final class CreateWorkEntryUseCase
{
    public function __invoke(CreateWorkEntryInputDto $input): void { ... }
    // НЕПРАВИЛЬНО: $this->useCase должен вызываться как $this->useCase->execute($input)
    // __invoke скрывает намерение и усложняет тесты
}
```
