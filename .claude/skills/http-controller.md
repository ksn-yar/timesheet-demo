---
name: http-controller
description: Правила создания тонкого Symfony HTTP-контроллера в инфраструктурном слое — invokable, один Use Case, OpenAPI-описание, интеграция с Value Resolver
---

# Symfony HTTP Controller: правила и практики

## Ключевые принципы

- **Контроллер — инфраструктурный компонент**, расположенный в слое Infrastructure конкретного Bounded Context
- **Тонкий контроллер**: принять HTTP-запрос -> вызвать Use Case -> вернуть HTTP-ответ
- **Один контроллер — один Use Case** (Single Action Controller)
- **Действие через `__invoke()`** — контроллер является invokable-классом
- **Не содержит бизнес-логику** — вся логика в Use Case домена (Command/Query Handler)
- **Не делает валидацию и десериализацию** — это ответственность Value Resolver
- **Получает DTO запроса** от кастомного Value Resolver через атрибут `#[ValueResolver(...)]`
- **Использует Input Transformer** для маппинга Request DTO → InputDto (обязательно для всех методов с телом: POST, PUT, PATCH; для GET — если формируется InputDto из параметров)
- **Описан атрибутами OpenAPI** (`OpenApi\Attributes`) для автодокументации через nelmio/api-doc-bundle
- **Имеет атрибут маршрута** `#[Route()]` из Symfony

---

## Архитектурное расположение

```
src/
├── {BoundedContext}/
│   └── Infrastructure/
│       ├── Controller/
│       │   └── CreateWorkEntryController.php   # контроллер
│       ├── ValueResolver/
│       │   └── CreateWorkEntryValueResolver.php # Value Resolver (см. skill value-resolver)
│       └── Dto/
│           └── CreateWorkEntryRequestDto.php    # DTO запроса (см. skill value-resolver)
```

**Почему так:**
- Контроллер зависит от Symfony (`AbstractController`, `Route`, `JsonResponse`) — это инфраструктура
- Контроллер привязан к HTTP-транспорту — размещается в `Infrastructure/Controller/`
- Группировка Controller, ValueResolver и Dto внутри `Infrastructure/` обеспечивает когезию HTTP-слоя

---

## Шаблон контроллера

### Контроллер для команды (POST/PUT/PATCH/DELETE)

```php
// src/{BoundedContext}/Infrastructure/Controller/{Action}{Entity}Controller.php

declare(strict_types=1);

namespace App\{BoundedContext}\Infrastructure\Controller;

use App\{BoundedContext}\Infrastructure\Dto\{Action}{Entity}RequestDto;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер {описание действия}. */
#[OA\Tag(name: '{BoundedContext}')]
#[OA\Post(
    summary: 'Краткое описание действия',
    description: 'Детальное описание, если необходимо.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: {Action}{Entity}RequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_CREATED,
    description: 'Ресурс успешно создан.',
)]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST,
    description: 'Невалидные данные запроса.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/{bounded-context}/{entities}', name: '{bounded_context}_{action}_{entity}', methods: ['POST'])]
final class {Action}{Entity}Controller extends AbstractController
{
    // Внедрение зависимостей: Use Case и Input Transformer через конструктор.
    // Input Transformer трансформирует Request DTO -> InputDto для Use Case (см. skill input-transformer).
    // Если используется Symfony Messenger — внедряем MessageBusInterface вместо конкретного Use Case.

    // public function __construct(
    //     private readonly {Action}{Entity}UseCase $useCase,
    //     private readonly {Action}{Entity}InputTransformer $transformer,
    // ) {}

    public function __invoke(
        #[ValueResolver({Action}{Entity}RequestDto::class)] {Action}{Entity}RequestDto $dto,
    ): JsonResponse {
        // DTO уже десериализован и валидирован через Value Resolver.
        // Transformer маппит Request DTO -> InputDto, Use Case выполняет сценарий.

        // $this->useCase->execute($this->transformer->transform($dto));

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
```

### Контроллер для запроса (GET)

```php
// src/{BoundedContext}/Infrastructure/Controller/Get{Entity}Controller.php

declare(strict_types=1);

namespace App\{BoundedContext}\Infrastructure\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения {сущности} по идентификатору. */
#[OA\Tag(name: '{BoundedContext}')]
#[OA\Get(
    summary: 'Получить {сущность} по ID',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор {сущности}.',
)]
#[OA\Response(
    response: Response::HTTP_OK,
    description: '{Сущность} найдена.',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            // ... остальные свойства ответа
        ],
    ),
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: '{Сущность} не найдена.',
)]
#[Route('/api/{bounded-context}/{entities}/{id}', name: '{bounded_context}_get_{entity}', methods: ['GET'])]
final class Get{Entity}Controller extends AbstractController
{
    public function __invoke(string $id): JsonResponse
    {
        // Формируем Query и отправляем в шину запросов (или вызываем Handler напрямую).

        // $result = $this->queryBus->ask(new Get{Entity}Query(id: $id));

        return new JsonResponse(null, Response::HTTP_OK);
    }
}
```

---

## Конкретный пример: создание записи рабочего времени

```php
// src/Timesheet/Infrastructure/Controller/CreateWorkEntryController.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Controller;

use App\Timesheet\Infrastructure\Dto\CreateWorkEntryRequestDto;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер создания записи рабочего времени. */
#[OA\Tag(name: 'Timesheet')]
#[OA\Post(
    summary: 'Создать запись рабочего времени',
    description: 'Создаёт новую запись рабочего времени для сотрудника.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: CreateWorkEntryRequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_CREATED,
    description: 'Запись рабочего времени успешно создана.',
)]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST,
    description: 'Невалидные данные запроса.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/timesheet/work-entries', name: 'timesheet_create_work_entry', methods: ['POST'])]
final class CreateWorkEntryController extends AbstractController
{
    // public function __construct(
    //     private readonly CreateWorkEntryUseCase $useCase,
    //     private readonly CreateWorkEntryInputTransformer $transformer,
    // ) {}

    public function __invoke(
        #[ValueResolver(CreateWorkEntryRequestDto::class)] CreateWorkEntryRequestDto $dto,
    ): JsonResponse {
        // DTO уже десериализован и валидирован через CreateWorkEntryValueResolver.
        // Transformer маппит Request DTO -> InputDto (см. skill input-transformer).

        // $this->useCase->execute($this->transformer->transform($dto));

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
```

---

## Правила именования

| Элемент           | Паттерн                                                             | Пример                        |
|-------------------|---------------------------------------------------------------------|-------------------------------|
| Класс контроллера | `{Action}{Entity}Controller`                                        | `CreateWorkEntryController`   |
| Имя маршрута      | `{bounded_context}_{action}_{entity}`                               | `timesheet_create_work_entry` |
| Путь маршрута     | `/api/{bounded-context}/{entities}`                                 | `/api/timesheet/work-entries` |
| Файл              | `src/{BC}/Infrastructure/Controller/{Action}{Entity}Controller.php` |                               |

**Действия (Action):**
- `Create` — создание (POST)
- `Update` — обновление (PUT/PATCH)
- `Delete` — удаление (DELETE)
- `Get` — получение одного (GET)
- `List` / `GetAll` — получение списка (GET)

---

## OpenAPI-атрибуты: справочник

Используются атрибуты из пакета `zircote/swagger-php` (namespace `OpenApi\Attributes`).

### Обязательные атрибуты

Каждый контроллер должен содержать:

1. **`#[OA\Tag(name: '...')]`** — на уровне класса, группирует эндпоинты в Swagger UI
2. **`#[OA\{Method}(summary: '...')]`** — на уровне класса, кратко описывает действие
3. **`#[OA\Response(...)]`** — минимум один ответ с HTTP-кодом и описанием

### Частые атрибуты

| Атрибут                                                | Где                         | Назначение                   |
|--------------------------------------------------------|-----------------------------|------------------------------|
| `#[OA\Tag]`                                            | класс                       | Группировка эндпоинтов       |
| `#[OA\Post]`, `#[OA\Get]`, `#[OA\Put]`, `#[OA\Delete]` | класс                       | HTTP-метод и описание        |
| `#[OA\RequestBody]`                                    | класс                       | Описание тела запроса        |
| `#[OA\JsonContent]`                                    | внутри RequestBody/Response | JSON-схема тела              |
| `#[OA\Response]`                                       | класс                       | Описание ответа с HTTP-кодом |
| `#[OA\Parameter]`                                      | класс                       | Path/query/header параметры  |
| `#[OA\Property]`                                       | внутри JsonContent          | Свойство JSON-объекта        |
| `#[OA\Schema]`                                         | на DTO-классе или inline    | Описание структуры данных    |

### Ссылка на DTO как схему

```php
// Вариант 1: ссылка на класс DTO (nelmio автоматически считает свойства)
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: CreateWorkEntryRequestDto::class)),
)]

// Вариант 2: inline-описание свойств
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['employeeId', 'startDate'],
        properties: [
            new OA\Property(property: 'employeeId', type: 'string', format: 'uuid'),
            new OA\Property(property: 'startDate', type: 'string', format: 'date'),
        ],
    ),
)]
```

---

## Регистрация сервисов

При стандартной конфигурации (autowire + autoconfigure включены, `App\\` сканируется из `src/`) **дополнительная регистрация не требуется**. Symfony автоматически:

- Обнаружит контроллер как наследника `AbstractController`
- Атрибут `#[Route(...)]` зарегистрирует маршрут
- Атрибут `#[ValueResolver(...)]` свяжет аргумент с нужным Value Resolver

---

## Взаимодействие контроллера с другими компонентами

```
HTTP Request
    │
    ▼
┌──────────────────────────┐
│  Symfony Router          │  #[Route('/api/...')]
└──────────┬───────────────┘
           │
           ▼
┌──────────────────────────┐
│  Value Resolver          │  Десериализация + Валидация DTO
│  (см. skill              │  При ошибках: ValidationFailedException
│   value-resolver)        │  или BadRequestHttpException
└──────────┬───────────────┘
           │ готовый Request DTO
           ▼
┌──────────────────────────┐
│  Controller::__invoke()  │  Тонкий: Request DTO -> Transformer -> Use Case -> Response
└──────────┬───────────────┘
           │ Request DTO
           ▼
┌──────────────────────────┐
│  Input Transformer       │  Request DTO -> InputDto
│  (см. skill              │  Маппинг полей для Application слоя
│   input-transformer)     │
└──────────┬───────────────┘
           │ InputDto
           ▼
┌──────────────────────────┐
│  Use Case Handler        │  Бизнес-логика (Application слой)
└──────────┬───────────────┘
           │
           ▼
       HTTP Response
```

---

## Чек-лист при создании контроллера

- [ ] Контроллер размещён в `src/{BoundedContext}/Infrastructure/Controller/`
- [ ] Контроллер наследует `AbstractController`
- [ ] Класс объявлен как `final`
- [ ] Контроллер содержит единственный публичный метод `__invoke()`
- [ ] Один контроллер обслуживает ровно один Use Case
- [ ] Контроллер не содержит бизнес-логику, валидацию или десериализацию
- [ ] Атрибут `#[Route()]` указан на уровне класса последним в списке атрибутов
- [ ] Имя маршрута следует паттерну `{bounded_context}_{action}_{entity}`
- [ ] Путь маршрута соответствует соглашениям проекта (например, `/api/` для REST API)
- [ ] DTO запроса получен через `#[ValueResolver(DtoClass::class)]` (для GET/POST/PUT/PATCH)
- [ ] `#[OA\Tag]` указан на классе
- [ ] `#[OA\{Method}]` с `summary` указан на уровне класса
- [ ] Как минимум один `#[OA\Response]` описывает успешный ответ
- [ ] `#[OA\RequestBody]` описывает тело запроса (для POST/PUT/PATCH)
- [ ] `#[OA\Parameter]` описывает path/query параметры (для GET/DELETE с параметрами)
- [ ] PHPDoc-комментарий на классе кратко описывает назначение контроллера
- [ ] Парный Value Resolver и DTO уже созданы (см. skill `value-resolver`)
- [ ] Для POST/PUT/PATCH: Input Transformer внедрён в конструктор и используется для маппинга Request DTO -> InputDto (см. skill `input-transformer`)

---

## Антипаттерны

```php
// ❌ Бизнес-логика в контроллере — должна быть в Use Case
final class CreateWorkEntryController extends AbstractController
{
    public function __invoke(...): JsonResponse
    {
        if ($dto->hours > 12) {
            throw new \Exception('Too many hours'); // логика принадлежит домену
        }
    }
}

// ❌ Десериализация и валидация в контроллере — это ответственность Value Resolver
final class CreateWorkEntryController extends AbstractController
{
    public function __invoke(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize($request->getContent(), ...);
        $errors = $this->validator->validate($dto);
        // дублируется в каждом контроллере, нарушает SRP
    }
}

// ❌ Несколько действий в одном контроллере — нарушает Single Action Controller
final class WorkEntryController extends AbstractController
{
    #[Route('/api/work-entries', methods: ['POST'])]
    public function create(...): JsonResponse { ... }

    #[Route('/api/work-entries/{id}', methods: ['GET'])]
    public function get(...): JsonResponse { ... }
}

// ❌ Не invokable контроллер — используйте __invoke()
final class CreateWorkEntryController extends AbstractController
{
    #[Route('/api/work-entries', methods: ['POST'])]
    public function execute(...): JsonResponse { ... }
}

// ❌ Контроллер без OpenAPI-атрибутов — API не документировано
final class CreateWorkEntryController extends AbstractController
{
    #[Route('/api/work-entries', methods: ['POST'])]
    public function __invoke(...): JsonResponse { ... }
    // Нет OA\Tag, OA\Post, OA\Response — эндпоинт не появится в Swagger UI
}

// ❌ Контроллер в неправильном слое
// src/Timesheet/Application/Controller/CreateWorkEntryController.php — НЕПРАВИЛЬНО
// src/Timesheet/Infrastructure/Controller/CreateWorkEntryController.php — ПРАВИЛЬНО

// ❌ Использование MapRequestPayload вместо кастомного Value Resolver
public function __invoke(#[MapRequestPayload] CreateWorkEntryRequestDto $dto): JsonResponse
// Используйте #[ValueResolver(CreateWorkEntryRequestDto::class)]
// и парный кастомный Value Resolver (см. skill value-resolver)

// ❌ Создание InputDto напрямую в контроллере — маппинг должен выполняться в Transformer
final class CreateWorkEntryController extends AbstractController
{
    public function __invoke(
        #[ValueResolver(CreateWorkEntryRequestDto::class)] CreateWorkEntryRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute(new CreateWorkEntryInputDto(  // ЗАПРЕЩЕНО: InputDto создаётся в контроллере
            employeeId: $dto->employeeId,
            startDate: $dto->startDate,
        ));
    }
}
// Всегда используйте Input Transformer: $this->useCase->execute($this->transformer->transform($dto))
```
