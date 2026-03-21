---
name: presenter
description: Правила создания Presenter в слое Interface Adapters — реализация OutputPort из Application-слоя, трансформация OutputDto в представление для конкретного транспорта (HTTP, CLI, HTML)
---

# Presenter: правила и практики

## Ключевые принципы

- **Presenter — компонент слоя Interface Adapters**, расположенный в Infrastructure конкретного Bounded Context
- **Use Case (Interactor) зависит только от интерфейса** `OutputPort` — не от конкретного Presenter
- **Use Case не возвращает данные** — он вызывает `$this->presenter->present(OutputDto $dto)` и завершает выполнение через `void`
- **Конкретный Presenter реализует OutputPort** и трансформирует данные для конкретного представления (JSON, CLI, HTML)
- **Presenter не содержит бизнес-логики** — только форматирование и трансформация данных
- **Один Use Case — один OutputPort — несколько конкретных Presenter** (HTTP, CLI, HTML)
- **Presenter хранит результат** и предоставляет его через метод `getResponse()` (или аналог)

---

## Архитектурное расположение

```
src/
├── {BoundedContext}/
│   ├── Application/
│   │   ├── UseCase/
│   │   │   └── {Action}{Entity}UseCase.php        # Use Case
│   │   ├── Dto/
│   │   │   └── {Action}{Entity}OutputDto.php          # выходное DTO от Use Case
│   │   └── Port/
│   │       └── {Action}{Entity}OutputPortInterface.php  # интерфейс Presenter (OutputPort)
│   └── Infrastructure/
│       └── Presenter/
│           ├── Http{Action}{Entity}Presenter.php       # JSON/HTTP презентер
│           ├── Cli{Action}{Entity}Presenter.php        # CLI презентер (если нужен)
│           └── Html{Action}{Entity}Presenter.php       # HTML/Twig презентер (если нужен)
```

**Почему так:**
- OutputPort и OutputDto находятся в Application — Use Case задаёт контракт, инфраструктура реализует (Dependency Inversion Principle)
- Конкретный Presenter зависит от Symfony (`JsonResponse`, `Twig`) — это инфраструктура
- Presenter привязан к конкретному транспорту — размещается в `Infrastructure/Presenter/`
- Разделение OutputPort и Presenter позволяет подменять представление без изменения Use Case

---

## Правила именования

| Элемент        | Паттерн                               | Пример                            |
|----------------|---------------------------------------|-----------------------------------|
| OutputPort     | `{Action}{Entity}OutputPortInterface` | `GetWorkEntryOutputPortInterface` |
| OutputDto      | `{Action}{Entity}OutputDto`           | `GetWorkEntryOutputDto`           |
| HTTP Presenter | `Http{Action}{Entity}Presenter`       | `HttpGetWorkEntryPresenter`       |
| CLI Presenter  | `Cli{Action}{Entity}Presenter`        | `CliGetWorkEntryPresenter`        |
| HTML Presenter | `Html{Action}{Entity}Presenter`       | `HtmlGetWorkEntryPresenter`       |
| Use Case       | `{Action}{Entity}UseCase`             | `GetWorkEntryUseCase`             |

**Действия (Action):**
- `Create` — создание
- `Update` — обновление
- `Delete` — удаление
- `Get` — получение одного
- `List` / `GetAll` — получение списка

---

## 1. OutputDto (Application слой)

OutputDto — выходной объект Use Case. Содержит только примитивные типы и не зависит от инфраструктуры.

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
        public ?string $optionalField = null,
    ) {}
}
```

### Конкретный пример

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
        public string $startDate,
        public string $endDate,
        public ?string $description,
    ) {}
}
```

### Правила для OutputDto

- **`final readonly class`** — иммутабельный после создания
- **Свойства через constructor promotion** — только примитивные типы (`string`, `int`, `float`, `bool`, `array`)
- **Не зависит от инфраструктуры** — нет `use Symfony\...`, `use Doctrine\...`
- **Не содержит атрибутов сериализации или OpenAPI** — это ответственность Presenter или Infrastructure DTO
- **Создаётся Use Case** из доменных объектов — маппинг Value Objects -> примитивы выполняется в Interactor

---

## 2. OutputPort — интерфейс (Application слой)

OutputPort определяет контракт, через который Use Case передаёт результат во внешний мир.

```php
// src/{BoundedContext}/Application/Port/{Action}{Entity}OutputPortInterface.php

declare(strict_types=1);

namespace App\{BoundedContext}\Application\Port;

use App\{BoundedContext}\Application\Dto\{Action}{Entity}OutputDto;

/** Контракт представления результата Use Case {описание действия}. */
interface {Action}{Entity}OutputPortInterface
{
    public function present({Action}{Entity}OutputDto $dto): void;
}
```

### Конкретный пример

```php
// src/Timesheet/Application/Port/GetWorkEntryOutputPortInterface.php

declare(strict_types=1);

namespace App\Timesheet\Application\Port;

use App\Timesheet\Application\Dto\GetWorkEntryOutputDto;

/** Контракт представления результата Use Case получения записи рабочего времени. */
interface GetWorkEntryOutputPortInterface
{
    public function present(GetWorkEntryOutputDto $dto): void;
}
```

### Правила для OutputPort

- **Один метод `present()`** — принимает соответствующий OutputDto, возвращает `void`
- **Не зависит от инфраструктуры** — нет `use Symfony\...`, `use Doctrine\...`
- **Расположен в Application слое** — Use Case зависит от этого интерфейса, а не от конкретного Presenter

---

## 3. Use Case / Interactor (Application слой)

Use Case использует OutputPort для передачи результата. Не возвращает данные напрямую.

```php
// src/{BoundedContext}/Application/UseCase/{Action}{Entity}UseCase.php

declare(strict_types=1);

namespace App\{BoundedContext}\Application\UseCase;

use App\{BoundedContext}\Application\Dto\{Action}{Entity}OutputDto;
use App\{BoundedContext}\Application\Port\{Action}{Entity}OutputPortInterface;
use App\{BoundedContext}\Domain\Repository\{Entity}RepositoryInterface;
use App\{BoundedContext}\Domain\ValueObject\{Entity}Id;

/** Use Case {описание действия}. */
final class {Action}{Entity}UseCase
{
    public function __construct(
        private readonly {Entity}RepositoryInterface $repository,
        private readonly {Action}{Entity}OutputPortInterface $presenter,   // <-- интерфейс, не конкретный класс
    ) {}

    public function execute({Action}{Entity}InputDto $input): void
    {
        // 1. Получить/создать доменный объект
        // 2. Выполнить бизнес-логику
        // 3. Передать результат через Presenter

        $this->presenter->present(new {Action}{Entity}OutputDto(
            // маппинг доменных объектов -> примитивы
        ));
    }
}
```

### Конкретный пример

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

## 4. HTTP JSON Presenter (Infrastructure слой)

HTTP Presenter реализует OutputPort и формирует `JsonResponse`. Хранит результат до момента, когда контроллер заберёт его через `getResponse()`.

```php
// src/{BoundedContext}/Infrastructure/Presenter/Http{Action}{Entity}Presenter.php

declare(strict_types=1);

namespace App\{BoundedContext}\Infrastructure\Presenter;

use App\{BoundedContext}\Application\Dto\{Action}{Entity}OutputDto;
use App\{BoundedContext}\Application\Port\{Action}{Entity}OutputPortInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/** HTTP-презентер результата Use Case {описание действия}. Формирует JSON-ответ. */
final class Http{Action}{Entity}Presenter implements {Action}{Entity}OutputPortInterface
{
    private ?JsonResponse $response = null;

    public function present({Action}{Entity}OutputDto $dto): void
    {
        $this->response = new JsonResponse([
            // маппинг OutputDto -> структура JSON-ответа
        ]);
    }

    public function getResponse(): JsonResponse
    {
        if ($this->response === null) {
            throw new \LogicException('Presenter has not been called yet.');
        }

        return $this->response;
    }
}
```

### Конкретный пример

```php
// src/Timesheet/Infrastructure/Presenter/HttpGetWorkEntryPresenter.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Presenter;

use App\Timesheet\Application\Dto\GetWorkEntryOutputDto;
use App\Timesheet\Application\Port\GetWorkEntryOutputPortInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/** HTTP-презентер результата Use Case получения записи рабочего времени. Формирует JSON-ответ. */
final class HttpGetWorkEntryPresenter implements GetWorkEntryOutputPortInterface
{
    private ?JsonResponse $response = null;

    public function present(GetWorkEntryOutputDto $dto): void
    {
        $this->response = new JsonResponse([
            'id'          => $dto->id,
            'employeeId'  => $dto->employeeId,
            'startDate'   => $dto->startDate,
            'endDate'     => $dto->endDate,
            'description' => $dto->description,
        ]);
    }

    public function getResponse(): JsonResponse
    {
        if ($this->response === null) {
            throw new \LogicException('Presenter has not been called yet.');
        }

        return $this->response;
    }
}
```

---

## 5. CLI Presenter (Infrastructure слой)

CLI Presenter реализует тот же OutputPort и форматирует данные для вывода в консоль.

```php
// src/Timesheet/Infrastructure/Presenter/CliGetWorkEntryPresenter.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Presenter;

use App\Timesheet\Application\Dto\GetWorkEntryOutputDto;
use App\Timesheet\Application\Port\GetWorkEntryOutputPortInterface;

/** CLI-презентер результата Use Case получения записи рабочего времени. Форматирует текстовый вывод. */
final class CliGetWorkEntryPresenter implements GetWorkEntryOutputPortInterface
{
    private ?string $output = null;

    public function present(GetWorkEntryOutputDto $dto): void
    {
        $this->output = sprintf(
            "Запись #%s\nСотрудник: %s\nПериод: %s — %s\nОписание: %s",
            $dto->id,
            $dto->employeeId,
            $dto->startDate,
            $dto->endDate,
            $dto->description ?? '—',
        );
    }

    public function getOutput(): string
    {
        if ($this->output === null) {
            throw new \LogicException('Presenter has not been called yet.');
        }

        return $this->output;
    }
}
```

---

## 6. Контроллер использует Presenter

Контроллер внедряет конкретный Presenter и Interactor, запускает Use Case и забирает готовый ответ.

```php
// src/Timesheet/Infrastructure/Controller/GetWorkEntryController.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Controller;

use App\Timesheet\Application\Dto\GetWorkEntryInputDto;
use App\Timesheet\Application\UseCase\GetWorkEntryUseCase;
use App\Timesheet\Infrastructure\Presenter\HttpGetWorkEntryPresenter;
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
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Запись не найдена.',
)]
#[Route('/api/timesheet/work-entries/{id}', name: 'timesheet_get_work_entry', methods: ['GET'])]
final class GetWorkEntryController extends AbstractController
{
    public function __construct(
        private readonly GetWorkEntryUseCase $interactor,
        private readonly HttpGetWorkEntryPresenter $presenter,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $this->interactor->execute(new GetWorkEntryInputDto($id));

        return $this->presenter->getResponse();
    }
}
```

---

## 7. Внедрение зависимостей (DI)

Use Case должен получать OutputPort через конструктор. Symfony DI связывает интерфейс с конкретной реализацией.

### Автоматическая привязка

Если в Bounded Context существует единственная реализация OutputPort — Symfony autowire свяжет автоматически.

### Явная привязка (services.php)

Если реализаций несколько или autowire не справляется — добавить alias в конфигурацию сервисов.

```php
// config/services.php

use App\Timesheet\Application\Port\GetWorkEntryOutputPortInterface;
use App\Timesheet\Infrastructure\Presenter\HttpGetWorkEntryPresenter;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(GetWorkEntryOutputPortInterface::class, HttpGetWorkEntryPresenter::class);
};
```

### Привязка через атрибут

```php
use App\Timesheet\Application\Port\GetWorkEntryOutputPortInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(GetWorkEntryOutputPortInterface::class)]
final class HttpGetWorkEntryPresenter implements GetWorkEntryOutputPortInterface
{
    // ...
}
```

---

## 8. Взаимодействие компонентов

```
HTTP Request
    │
    ▼
┌──────────────────────────┐
│  Controller::__invoke()  │  Создаёт InputDto, вызывает Interactor
└──────────┬───────────────┘
           │ InputDto
           ▼
┌──────────────────────────┐
│  Interactor::execute()   │  Бизнес-логика, доменные операции
│  (Application слой)      │  Вызывает $presenter->present(OutputDto)
└──────────┬───────────────┘
           │ OutputDto
           ▼
┌──────────────────────────┐
│  Presenter::present()    │  Трансформирует OutputDto в JsonResponse
│  (Infrastructure слой)   │  Сохраняет результат в $this->response
└──────────┬───────────────┘
           │
           ▼
┌──────────────────────────┐
│  Controller              │  Забирает $presenter->getResponse()
└──────────┬───────────────┘
           │
           ▼
       HTTP Response
```

---

## 9. Unit-тест Presenter

```php
// tests/Unit/{BoundedContext}/Infrastructure/Presenter/Http{Action}{Entity}PresenterTest.php

declare(strict_types=1);

namespace App\Tests\Unit\{BoundedContext}\Infrastructure\Presenter;

use App\{BoundedContext}\Application\Dto\{Action}{Entity}OutputDto;
use App\{BoundedContext}\Infrastructure\Presenter\Http{Action}{Entity}Presenter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Проверяет трансформацию OutputDto в JSON-ответ. */
final class Http{Action}{Entity}PresenterTest extends TestCase
{
    #[Test]
    public function presentsOutputDtoAsJsonResponse(): void
    {
        $presenter = new Http{Action}{Entity}Presenter();
        $dto = new {Action}{Entity}OutputDto(
            // тестовые данные
        );

        $presenter->present($dto);
        $response = $presenter->getResponse();

        self::assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        // Проверка полей ответа
    }

    #[Test]
    public function throwsLogicExceptionWhenNotPresented(): void
    {
        $presenter = new Http{Action}{Entity}Presenter();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Presenter has not been called yet.');

        $presenter->getResponse();
    }
}
```

### Конкретный пример

```php
// tests/Unit/Timesheet/Infrastructure/Presenter/HttpGetWorkEntryPresenterTest.php

declare(strict_types=1);

namespace App\Tests\Unit\Timesheet\Infrastructure\Presenter;

use App\Timesheet\Application\Dto\GetWorkEntryOutputDto;
use App\Timesheet\Infrastructure\Presenter\HttpGetWorkEntryPresenter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Проверяет трансформацию GetWorkEntryOutputDto в JSON-ответ. */
final class HttpGetWorkEntryPresenterTest extends TestCase
{
    #[Test]
    public function presentsWorkEntryAsJsonResponse(): void
    {
        $presenter = new HttpGetWorkEntryPresenter();
        $dto = new GetWorkEntryOutputDto(
            id: '550e8400-e29b-41d4-a716-446655440000',
            employeeId: '660e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-01',
            endDate: '2026-03-15',
            description: 'Работа над проектом X',
        );

        $presenter->present($dto);
        $response = $presenter->getResponse();

        self::assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $data['id']);
        self::assertSame('660e8400-e29b-41d4-a716-446655440000', $data['employeeId']);
        self::assertSame('2026-03-01', $data['startDate']);
        self::assertSame('2026-03-15', $data['endDate']);
        self::assertSame('Работа над проектом X', $data['description']);
    }

    #[Test]
    public function presentsWorkEntryWithNullDescription(): void
    {
        $presenter = new HttpGetWorkEntryPresenter();
        $dto = new GetWorkEntryOutputDto(
            id: '550e8400-e29b-41d4-a716-446655440000',
            employeeId: '660e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-01',
            endDate: '2026-03-15',
            description: null,
        );

        $presenter->present($dto);
        $response = $presenter->getResponse();

        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNull($data['description']);
    }

    #[Test]
    public function throwsLogicExceptionWhenNotPresented(): void
    {
        $presenter = new HttpGetWorkEntryPresenter();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Presenter has not been called yet.');

        $presenter->getResponse();
    }
}
```

---

## Чек-лист при создании Presenter

### OutputDto (Application)

- [ ] OutputDto размещён в `src/{BoundedContext}/Application/Dto/`
- [ ] Класс объявлен как `final readonly class`
- [ ] Свойства продвигаются через конструктор (constructor promotion)
- [ ] Только примитивные типы (`string`, `int`, `float`, `bool`, `array`)
- [ ] Нет зависимостей от инфраструктуры (`Symfony`, `Doctrine`)
- [ ] PHPDoc-комментарий на классе описывает назначение

### OutputPort (Application)

- [ ] OutputPort размещён в `src/{BoundedContext}/Application/Port/`
- [ ] Один метод `present()` — принимает OutputDto, возвращает `void`
- [ ] Нет зависимостей от инфраструктуры
- [ ] PHPDoc-комментарий на интерфейсе описывает контракт

### Use Case / Interactor (Application)

- [ ] Use Case зависит от OutputPort (интерфейса), не от конкретного Presenter
- [ ] Use Case не возвращает данные — вызывает `$presenter->present(...)`
- [ ] Use Case не зависит от `JsonResponse`, `Request` или других HTTP-объектов

### HTTP Presenter (Infrastructure)

- [ ] Presenter размещён в `src/{BoundedContext}/Infrastructure/Presenter/`
- [ ] Класс объявлен как `final`
- [ ] Implements интерфейс OutputPort из Application слоя
- [ ] Метод `present()` сохраняет результат в `$this->response`
- [ ] Метод `getResponse()` возвращает `JsonResponse`
- [ ] `getResponse()` бросает `\LogicException`, если `present()` не был вызван
- [ ] PHPDoc-комментарий на классе описывает назначение

### DI-контейнер

- [ ] OutputPort привязан к конкретному Presenter (alias в `services.php` или `#[AsAlias]`)
- [ ] Autowire корректно разрешает зависимости Interactor

### Тесты

- [ ] Unit-тест проверяет трансформацию OutputDto в JSON-ответ
- [ ] Unit-тест проверяет обработку nullable полей
- [ ] Unit-тест проверяет `\LogicException` при вызове `getResponse()` без `present()`

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

// -- Бизнес-логика в Presenter -- Presenter только форматирует
final class HttpGetWorkEntryPresenter implements GetWorkEntryOutputPortInterface
{
    public function present(GetWorkEntryOutputDto $dto): void
    {
        if ($dto->startDate > $dto->endDate) {
            throw new \DomainException('...'); // логика принадлежит домену
        }
        $this->response = new JsonResponse([...]);
    }
}

// -- Presenter обращается к репозиторию или сервисам
final class HttpGetWorkEntryPresenter implements GetWorkEntryOutputPortInterface
{
    public function __construct(
        private readonly WorkEntryRepositoryInterface $repository, // ЗАПРЕЩЕНО
    ) {}
}
// Presenter получает готовые данные через OutputDto — не загружает их сам

// -- Use Case зависит от инфраструктурных типов
use Symfony\Component\HttpFoundation\JsonResponse;

final class GetWorkEntryUseCase
{
    public function execute(GetWorkEntryInputDto $input): JsonResponse
    {
        // Use Case не должен знать о JsonResponse — это ответственность Presenter
    }
}

// -- Presenter без сохранения состояния (stateless)
final class HttpGetWorkEntryPresenter implements GetWorkEntryOutputPortInterface
{
    public function present(GetWorkEntryOutputDto $dto): JsonResponse
    {
        return new JsonResponse([...]); // возврат вместо сохранения нарушает контракт void
    }
}
// OutputPort::present() возвращает void — результат сохраняется в поле класса

// -- Один Presenter для нескольких Use Case
final class HttpWorkEntryPresenter implements
    GetWorkEntryOutputPortInterface,
    ListWorkEntriesOutputPortInterface // нарушает SRP
{
    // Один Presenter — один OutputPort
}

// -- OutputDto с доменными Value Objects
final readonly class GetWorkEntryOutputDto
{
    public function __construct(
        public WorkEntryId $id, // VO из домена — используйте string
    ) {}
}
// OutputDto оперирует примитивными типами

// -- Presenter в Application слое
// src/Timesheet/Application/Presenter/HttpGetWorkEntryPresenter.php — НЕПРАВИЛЬНО
// src/Timesheet/Infrastructure/Presenter/HttpGetWorkEntryPresenter.php — ПРАВИЛЬНО
```
