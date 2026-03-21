---
name: clean-architecture
description: Принципы и практики применения Чистой архитектуры Роберта Мартина в PHP-проектах
---

# Чистая архитектура в PHP-проектах

## Основная идея

Чистая архитектура (Clean Architecture) Роберта Мартина строится вокруг одного правила: **зависимости направлены только внутрь**. Внешние детали (фреймворки, БД, HTTP) зависят от ядра — но не наоборот. Ядро — это бизнес-логика, которая не знает ничего о Symfony, Doctrine или любом другом инфраструктурном коде.

---

## Слои архитектуры

```
┌──────────────────────────────────────────────────────┐
│  Frameworks & Drivers (Infrastructure)               │
│  ┌────────────────────────────────────────────────┐  │
│  │  Interface Adapters (Controllers, Presenters)  │  │
│  │  ┌──────────────────────────────────────────┐  │  │
│  │  │  Application (Use Cases)                 │  │  │
│  │  │  ┌────────────────────────────────────┐  │  │  │
│  │  │  │  Domain (Entities, Value Objects)  │  │  │  │
│  │  │  └────────────────────────────────────┘  │  │  │
│  │  └──────────────────────────────────────────┘  │  │
│  └────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────┘
```

### 1. Domain (Entities)

Самый внутренний слой. Содержит корпоративные бизнес-правила.

- **Entity** — объект с идентичностью и жизненным циклом
- **Value Object** — неизменяемый объект, определяемый значением
- **Domain Event** — факт, произошедший в домене
- **Domain Service** — логика, не принадлежащая одной сущности
- **Repository Interface** — контракт доступа к данным (только интерфейс!)

**Правила:**
- Нет зависимостей от внешних слоёв
- Нет зависимостей от фреймворков (`use Symfony\...` — запрещено)
- Инварианты защищены в конструкторе или фабричном методе

```php
// ✅ Правильно: Entity с защищёнными инвариантами
final class WorkEntry
{
    private function __construct(
        private readonly WorkEntryId $id,
        private readonly EmployeeId  $employeeId,
        private readonly DateRange   $period,
        private readonly Hours       $hours,
    ) {}

    public static function create(
        WorkEntryId $id,
        EmployeeId  $employeeId,
        DateRange   $period,
        Hours       $hours,
    ): self {
        if ($hours->isZero()) {
            throw new InvalidWorkEntryException('Work entry cannot have zero hours.');
        }

        return new self($id, $employeeId, $period, $hours);
    }
}
```

```php
// ✅ Правильно: Value Object — неизменяемый, с самовалидацией
final class Hours
{
    public function __construct(private readonly float $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('Hours cannot be negative.');
        }
    }

    public function isZero(): bool
    {
        return $this->value === 0.0;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

---

### 2. Application (Use Cases)

Содержит прикладные бизнес-правила. Оркестрирует поток данных между доменом и внешним миром.

- **Command / Query** — входные данные Use Case (CQRS)
- **Command Handler / Query Handler** — реализация Use Case
- **Port (интерфейс)** — контракт к инфраструктуре (Mailer, FileStorage, EventBus…)
- **Application Service** — когда Use Case требует координации нескольких агрегатов

**Правила:**
- Знает о домене, но не об инфраструктуре
- Работает только с интерфейсами (Repository, Mailer и т.д.)
- Не содержит бизнес-логики — только оркестрацию

```php
// ✅ Command (DTO входных данных)
final readonly class RegisterWorkEntryCommand
{
    public function __construct(
        public string $employeeId,
        public string $startDate,
        public string $endDate,
        public float  $hours,
    ) {}
}
```

```php
// ✅ Command Handler (Use Case)
final class RegisterWorkEntryHandler
{
    public function __construct(
        private readonly WorkEntryRepository $repository,
        private readonly WorkEntryIdGenerator $idGenerator,
        private readonly EventBusInterface    $eventBus,
    ) {}

    public function __invoke(RegisterWorkEntryCommand $command): void
    {
        $entry = WorkEntry::create(
            $this->idGenerator->generate(),
            EmployeeId::fromString($command->employeeId),
            DateRange::fromStrings($command->startDate, $command->endDate),
            new Hours($command->hours),
        );

        $this->repository->save($entry);
        $this->eventBus->publish(...$entry->pullDomainEvents());
    }
}
```

---

### 3. Interface Adapters (Infrastructure)

Преобразует данные между форматом Use Case и форматом внешнего мира (HTTP, CLI, очереди). В данном проекте компоненты Interface Adapters размещаются в слое `Infrastructure` конкретного Bounded Context.

- **Controller** — принимает HTTP-запрос (через Value Resolver), вызывает Use Case через Transformer, возвращает Response
- **Presenter** — преобразует OutputDto Use Case в HTTP-ответ
- **Input Transformer** — маппит Request DTO → InputDto
- **CLI Command** — Symfony Console команда как точка входа

**Правила:**
- Не содержит бизнес-логики
- Не работает напрямую с Domain-объектами из вьюшек/ответов — использует DTO
- Тонкий слой: принял → трансформировал → передал
- Десериализация и валидация — через Value Resolver, не в контроллере

```php
// ✅ Controller: тонкий, без логики, через Value Resolver и Transformer
final class RegisterWorkEntryController extends AbstractController
{
    public function __construct(
        private readonly RegisterWorkEntryUseCase $useCase,
        private readonly RegisterWorkEntryInputTransformer $transformer,
    ) {}

    #[Route('/api/work-entries', methods: ['POST'])]
    public function __invoke(
        #[ValueResolver(RegisterWorkEntryRequestDto::class)] RegisterWorkEntryRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
```

---

### 4. Infrastructure (Frameworks & Drivers)

Конкретные реализации интерфейсов: база данных, почта, очереди, внешние API.

- **DoctrineWorkEntryRepository** — реализует `WorkEntryRepository`
- **SymfonyEventBus** — реализует `EventBusInterface`
- **SmtpMailer** — реализует `MailerInterface`

**Правила:**
- Реализует интерфейсы из Application/Domain
- Зависит от фреймворков — это единственный слой, которому это разрешено
- Не содержит бизнес-логики

```php
// ✅ Репозиторий: реализует доменный интерфейс
final class DoctrineWorkEntryRepository implements WorkEntryRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(WorkEntry $entry): void
    {
        $this->em->persist($entry);
        $this->em->flush();
    }

    public function findById(WorkEntryId $id): ?WorkEntry
    {
        return $this->em->find(WorkEntry::class, $id->value());
    }
}
```

---

## Структура директорий

Верхний уровень — Bounded Context, внутри которого три слоя: `Domain`, `Application`, `Infrastructure`.

```
src/
├── Timesheet/                         ← Bounded Context
│   ├── Domain/
│   │   ├── Entity/
│   │   │   └── WorkEntry.php          # Entity / Aggregate Root
│   │   ├── ValueObject/
│   │   │   ├── WorkEntryId.php        # Value Object
│   │   │   ├── Hours.php
│   │   │   └── DateRange.php
│   │   ├── Repository/
│   │   │   └── WorkEntryRepositoryInterface.php   # Repository Interface
│   │   └── Event/
│   │       └── WorkEntryRegistered.php
│   ├── Application/
│   │   ├── UseCase/
│   │   │   ├── RegisterWorkEntryUseCase.php
│   │   │   └── GetWorkEntryUseCase.php
│   │   ├── Dto/
│   │   │   ├── RegisterWorkEntryInputDto.php
│   │   │   ├── GetWorkEntryInputDto.php
│   │   │   └── GetWorkEntryOutputDto.php
│   │   └── Port/
│   │       ├── GetWorkEntryOutputPortInterface.php
│   │       ├── EventBusInterface.php
│   │       └── WorkEntryIdGeneratorInterface.php
│   └── Infrastructure/
│       ├── Controller/
│       │   └── RegisterWorkEntryController.php    # HTTP-контроллер
│       ├── Presenter/
│       │   └── HttpGetWorkEntryPresenter.php      # Presenter
│       ├── Transformer/
│       │   └── RegisterWorkEntryInputTransformer.php
│       ├── ValueResolver/
│       │   └── RegisterWorkEntryValueResolver.php
│       ├── Dto/
│       │   └── RegisterWorkEntryRequestDto.php
│       └── Repository/
│           └── DoctrineWorkEntryRepository.php
├── Shared/                            ← Shared Kernel (минимален)
│   └── Domain/
│       └── ValueObject/
│           └── Money.php
└── Persistence/                       ← единый Doctrine-домен
    ├── Entity/
    │   └── WorkEntry.php
    └── Repository/
        └── WorkEntryRepository.php
```

---

## Правило зависимостей — шпаргалка

| Слой | Знает о | Не знает о |
|---|---|---|
| Domain | только о себе | Application, Infrastructure |
| Application | Domain | Infrastructure, фреймворках |
| Infrastructure | Domain (интерфейсы), Application | — |

---

## Антипаттерны — что запрещено

```php
// ❌ Symfony-аннотации в Domain Entity
#[ORM\Entity]
class WorkEntry { ... }

// ❌ Бизнес-логика в Controller
public function register(Request $request): Response {
    if ($hours > 12) {
        throw new \Exception('Too many hours'); // ← это должно быть в домене
    }
}

// ❌ Прямой вызов репозитория в Domain Entity
class WorkEntry {
    public function getSiblings(): array {
        return $this->repository->findByEmployee($this->employeeId); // ← НЕЛЬЗЯ
    }
}

// ❌ Response/Request в Application Use Case
class RegisterWorkEntryHandler {
    public function handle(Request $request): JsonResponse { ... } // ← зависимость на HTTP
}

// ❌ Возврат Doctrine-коллекций из репозитория
interface WorkEntryRepository {
    public function findAll(): Collection; // ← Doctrine\Common\Collections — инфраструктурный тип
}
```

---

## Тестирование по слоям

| Слой | Тип тестов | Моки |
|---|---|---|
| Domain | Unit | нет |
| Application | Unit | Repository, EventBus и т.д. через моки |
| Infrastructure | Integration | реальная БД, реальные сервисы |
| Infrastructure/Controller | Functional/E2E | весь стек или Symfony WebTestCase |

```php
// ✅ Unit-тест Use Case с мок-репозиторием
class RegisterWorkEntryUseCaseTest extends TestCase
{
    public function test_registers_work_entry(): void
    {
        $repository = $this->createMock(WorkEntryRepositoryInterface::class);
        $repository->expects($this->once())->method('save');

        $useCase = new RegisterWorkEntryUseCase(
            $repository,
            new UuidWorkEntryIdGenerator(),
            new InMemoryEventBus(),
        );

        $useCase->execute(new RegisterWorkEntryInputDto(
            employeeId: 'emp-1',
            startDate:  '2026-03-01',
            endDate:    '2026-03-15',
            hours:      80.0,
        ));
    }
}
```

---

## Связь с DDD

Чистая архитектура хорошо сочетается с Domain-Driven Design:

- **Aggregate Root** → Entity (Domain)
- **Value Object** → Domain
- **Repository** → интерфейс `{Entity}RepositoryInterface` в Domain, реализация в Infrastructure
- **Domain Service** → Domain или Application
- **Application Service** → Application Use Case Handler (метод `execute()`)
- **Bounded Context** → отдельный модуль/namespace верхнего уровня (`src/{BoundedContext}/`)

---

## Ключевые принципы — итог

1. **Правило зависимостей**: зависимости только внутрь
2. **Инверсия зависимостей**: Application работает с интерфейсами — Infrastructure их реализует
3. **Разделение ответственности**: каждый слой делает одно дело
4. **Тестируемость**: домен и use cases тестируются без фреймворка
5. **Независимость от фреймворка**: бизнес-логика не знает о Symfony/Laravel
6. **Независимость от БД**: смена Doctrine на Redis — только изменение Infrastructure
