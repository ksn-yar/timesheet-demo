---
name: ddd
description: Тактические и стратегические паттерны Domain-Driven Design в PHP-проектах
---

# Domain-Driven Design в PHP-проектах

## Ключевая идея

DDD — это подход к проектированию, при котором **модель предметной области** является центром всех архитектурных решений. Код отражает язык бизнеса (Ubiquitous Language), а сложность управляется через явные границы (Bounded Contexts).

DDD делится на два уровня:
- **Стратегический** — как разбить систему на части (Bounded Contexts, Context Map)
- **Тактический** — как моделировать внутри одного контекста (Entity, Aggregate, Repository…)

---

## Стратегические паттерны

### Ubiquitous Language

Единый язык между разработчиками и экспертами предметной области. Используется в коде, документации, разговорах.

**Правила:**
- Имена классов, методов, переменных берутся из языка бизнеса
- Один термин — одно значение внутри Bounded Context
- При разногласии с экспертом — менять код, не придумывать синонимы

```php
// ❌ Технический язык — не отражает домен
class UserRecord {
    public function updateStatus(int $statusCode): void { ... }
}

// ✅ Ubiquitous Language
class Employee {
    public function approve(): void { ... }
    public function suspend(SuspensionReason $reason): void { ... }
}
```

---

### Bounded Context

Явная граница, внутри которой модель и язык однозначны. Один и тот же термин в разных контекстах может означать разное.

```
┌─────────────────────┐    ┌─────────────────────┐
│   Timesheet Context │    │   Payroll Context    │
│                     │    │                     │
│  Employee           │    │  Employee           │
│  - name             │    │  - salary           │
│  - workEntries      │    │  - taxRate          │
│  - approvalStatus   │    │  - bankAccount      │
└─────────────────────┘    └─────────────────────┘
         │                           │
         └──────── Integration ──────┘
```

**Структура директорий по Bounded Contexts:**
```
src/
├── Timesheet/         ← Bounded Context
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
├── Payroll/           ← Bounded Context
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
└── Shared/            ← Shared Kernel (минимален)
    └── Domain/
        └── ValueObject/
            └── Money.php
```

---

### Context Map (карта контекстов)

Описывает отношения между Bounded Contexts:

| Паттерн | Описание | Когда применять |
|---|---|---|
| **Shared Kernel** | Общая часть модели | Тесно связанные контексты одной команды |
| **Customer–Supplier** | Один контекст зависит от другого | Один API, другой — потребитель |
| **Conformist** | Потребитель принимает модель поставщика | Внешний сервис, изменить нельзя |
| **Anti-Corruption Layer** | Адаптер для защиты от чужой модели | Интеграция с легаси или внешним API |
| **Open Host Service** | Публичный протокол для множества клиентов | REST API, Event Bus |

```php
// ✅ Anti-Corruption Layer: защищаем свою модель от внешней
final class ExternalPayrollAdapter implements PayrollPort
{
    public function __construct(
        private readonly ExternalPayrollClient $client,
    ) {}

    public function getSalary(EmployeeId $id): Money
    {
        // Переводим чужую модель в нашу
        $response = $this->client->fetchEmployee($id->value());

        return Money::ofMinorUnits(
            $response['salary_cents'],
            Currency::fromCode($response['currency']),
        );
    }
}
```

---

## Тактические паттерны

### Entity

Объект с **уникальной идентичностью**, которая сохраняется на протяжении всего жизненного цикла. Два Entity с одинаковыми атрибутами — разные объекты, если у них разные ID.

**Правила:**
- Идентичность определяется ID, а не состоянием
- Инварианты защищены — конструктор или фабричный метод
- Использовать фабричный метод для восстановления из персистентного хранилища
- Изменения состояния — только через именованные методы (не сеттеры)
- Публичные сеттеры — запрещены

```php
final class WorkEntry
{
    private WorkEntryStatus $status;

    private function __construct(
        private readonly WorkEntryId  $id,
        private readonly EmployeeId   $employeeId,
        private readonly DateRange    $period,
        private Hours                 $hours,
    ) {
        $this->status = WorkEntryStatus::Draft;
    }

    public static function create(
        WorkEntryId $id,
        EmployeeId  $employeeId,
        DateRange   $period,
        Hours       $hours,
    ): self {
        if ($hours->isZero()) {
            throw new InvalidWorkEntryException('Hours cannot be zero.');
        }

        return new self($id, $employeeId, $period, $hours);
    }

    public static function restore(
        WorkEntryId $id,
        EmployeeId  $employeeId,
        DateRange   $period,
        Hours       $hours,
    ): self {
        return new self($id, $email, $name, $createdAt);
    }

    public function submit(): void
    {
        if (!$this->status->isDraft()) {
            throw new WorkEntryAlreadySubmittedException($this->id);
        }

        $this->status = WorkEntryStatus::Submitted;
        $this->record(new WorkEntrySubmitted($this->id, $this->employeeId));
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
```

---

### Value Object

Объект без идентичности, определяемый **значением**. Неизменяем. Два Value Object с одинаковыми значениями — равны.

**Правила:**
- Иммутабельный: нет сеттеров, нет изменения состояния
- Самовалидирующийся: невалидное состояние невозможно создать
- Метод `equals()` для сравнения по значению
- Вместо изменения — создаёт новый экземпляр

```php
final class DateRange
{
    public function __construct(
        private readonly \DateTimeImmutable $start,
        private readonly \DateTimeImmutable $end,
    ) {
        if ($end < $start) {
            throw new \InvalidArgumentException(
                sprintf('End date %s cannot be before start date %s.',
                    $end->format('Y-m-d'),
                    $start->format('Y-m-d'),
                )
            );
        }
    }

    public static function fromStrings(string $start, string $end): self
    {
        return new self(
            new \DateTimeImmutable($start),
            new \DateTimeImmutable($end),
        );
    }

    public function contains(\DateTimeImmutable $date): bool
    {
        return $date >= $this->start && $date <= $this->end;
    }

    public function overlaps(self $other): bool
    {
        return $this->start <= $other->end && $this->end >= $other->start;
    }

    public function equals(self $other): bool
    {
        return $this->start == $other->start && $this->end == $other->end;
    }

    public function extendTo(\DateTimeImmutable $newEnd): self
    {
        return new self($this->start, $newEnd); // новый объект, не мутация
    }
}
```

```php
// ✅ Типизированный ID как Value Object — никогда не путаем ID разных сущностей
final class WorkEntryId
{
    public function __construct(private readonly string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid WorkEntryId: {$value}");
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::v7()->toRfc4122());
    }

    public function value(): string { return $this->value; }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

---

### Aggregate & Aggregate Root

**Aggregate** — кластер связанных объектов, которые изменяются как единое целое. **Aggregate Root** — единственная точка входа в агрегат.

**Правила:**
- Снаружи доступен только Aggregate Root
- Внутренние Entity и Value Objects не доступны напрямую извне
- Каждый агрегат — транзакционная граница: одна транзакция = один агрегат
- Ссылки между агрегатами — только по ID, не по объекту
- Агрегат маленький: если нужно много данных — это сигнал разбить

```php
// ✅ Aggregate Root: TimesheetPeriod содержит WorkEntry внутри
final class TimesheetPeriod
{
    /** @var WorkEntry[] */
    private array $entries = [];

    private function __construct(
        private readonly TimesheetPeriodId $id,
        private readonly EmployeeId        $employeeId,  // ← ссылка по ID, не объект
        private readonly Month             $month,
        private TimesheetStatus            $status,
    ) {}

    public function addEntry(WorkEntryId $entryId, DateRange $period, Hours $hours): void
    {
        $this->guardNotClosed();
        $this->guardNoOverlap($period);

        $this->entries[] = WorkEntry::create($entryId, $period, $hours);
        $this->record(new WorkEntryAdded($this->id, $entryId));
    }

    public function submit(): void
    {
        if (empty($this->entries)) {
            throw new EmptyTimesheetException($this->id);
        }

        $this->guardNotClosed();
        $this->status = TimesheetStatus::Submitted;
        $this->record(new TimesheetSubmitted($this->id, $this->employeeId));
    }

    private function guardNotClosed(): void
    {
        if ($this->status->isClosed()) {
            throw new TimesheetAlreadyClosedException($this->id);
        }
    }

    private function guardNoOverlap(DateRange $period): void
    {
        foreach ($this->entries as $entry) {
            if ($entry->period()->overlaps($period)) {
                throw new OverlappingWorkEntryException($period);
            }
        }
    }
}
```

---

### Repository

Абстракция коллекции агрегатов. Скрывает детали хранения. Интерфейс — в домене, реализация — в инфраструктуре.

**Правила:**
- Интерфейс в `Domain/`, реализация в `Infrastructure/`
- Методы принимают и возвращают доменные объекты (не массивы, не DTO)
- Только для Aggregate Root — не для внутренних Entity
- Один репозиторий — один агрегат

```php
// ✅ Domain: только интерфейс
interface TimesheetPeriodRepository
{
    public function save(TimesheetPeriod $timesheet): void;

    public function findById(TimesheetPeriodId $id): ?TimesheetPeriod;

    /** @return TimesheetPeriod[] */
    public function findByEmployee(EmployeeId $employeeId): array;

    public function findByEmployeeAndMonth(EmployeeId $employeeId, Month $month): ?TimesheetPeriod;
    
    public function remove(TimesheetPeriod $timesheet): void;
}
```

```php
// ✅ Infrastructure: Doctrine-реализация
final class DoctrineTimesheetPeriodRepository implements TimesheetPeriodRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(TimesheetPeriod $timesheet): void
    {
        $this->em->persist($timesheet);
        $this->em->flush();
    }

    public function findById(TimesheetPeriodId $id): ?TimesheetPeriod
    {
        return $this->em->find(TimesheetPeriod::class, $id->value());
    }

    public function findByEmployee(EmployeeId $employeeId): array
    {
        return $this->em->createQueryBuilder()
            ->select('t')
            ->from(TimesheetPeriod::class, 't')
            ->where('t.employeeId = :employeeId')
            ->setParameter('employeeId', $employeeId->value())
            ->getQuery()
            ->getResult();
    }
}
```

---

### Domain Service

Логика, которая не принадлежит одной сущности — затрагивает несколько агрегатов или требует внешних данных.

**Правила:**
- Нет состояния (stateless)
- Имя отражает бизнес-операцию, не техническую деталь
- Если логика принадлежит одной сущности — она должна быть там, а не в сервисе

```php
// ✅ Domain Service: проверка пересечений между двумя агрегатами
final class WorkEntryOverlapChecker
{
    public function __construct(
        private readonly TimesheetPeriodRepository $repository,
    ) {}

    public function hasOverlapForEmployee(
        EmployeeId $employeeId,
        DateRange  $newPeriod,
    ): bool {
        $timesheets = $this->repository->findByEmployee($employeeId);

        foreach ($timesheets as $timesheet) {
            if ($timesheet->hasOverlappingEntry($newPeriod)) {
                return true;
            }
        }

        return false;
    }
}
```

---

### Domain Events

Факт, случившийся в домене. Используются для коммуникации между Bounded Contexts и реакции на изменения.

**Правила:**
- Имя в прошедшем времени: `WorkEntrySubmitted`, `TimesheetApproved`
- Неизменяемые (readonly)
- Содержат только ID и примитивы — не ссылки на объекты
- Агрегат накапливает события, Use Case публикует их после сохранения

```php
// ✅ Domain Event
final readonly class TimesheetSubmitted
{
    public \DateTimeImmutable $occurredAt;

    public function __construct(
        public TimesheetPeriodId $timesheetId,
        public EmployeeId        $employeeId,
        public Month             $month,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
```

```php
// ✅ Агрегат записывает события, не публикует сам
trait RecordsDomainEvents
{
    private array $domainEvents = [];

    private function record(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
```

```php
// ✅ Use Case публикует события после сохранения агрегата
final class SubmitTimesheetHandler
{
    public function __invoke(SubmitTimesheetCommand $command): void
    {
        $timesheet = $this->repository->findById(
            TimesheetPeriodId::fromString($command->timesheetId)
        ) ?? throw new TimesheetNotFoundException($command->timesheetId);

        $timesheet->submit();

        $this->repository->save($timesheet);
        $this->eventBus->publish(...$timesheet->pullDomainEvents()); // ← после сохранения
    }
}
```

---

### Factory

Инкапсулирует логику создания сложных агрегатов или Value Objects.

```php
// ✅ Доменная фабрика — когда создание нетривиально
final class TimesheetPeriodFactory
{
    public function __construct(
        private readonly TimesheetPeriodIdGenerator $idGenerator,
    ) {}

    public function createForEmployee(EmployeeId $employeeId, Month $month): TimesheetPeriod
    {
        return TimesheetPeriod::open(
            $this->idGenerator->generate(),
            $employeeId,
            $month,
        );
    }
}
```

---

### Specification

Инкапсулирует бизнес-правило в объекте. Можно комбинировать через `and`, `or`, `not`.

```php
interface Specification
{
    public function isSatisfiedBy(mixed $candidate): bool;
}

final class SubmittableTimesheetSpecification implements Specification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        assert($candidate instanceof TimesheetPeriod);

        return $candidate->isDraft()
            && !$candidate->isEmpty()
            && $candidate->month()->isNotFuture();
    }
}

// Использование в доменном сервисе или Use Case
if (!$spec->isSatisfiedBy($timesheet)) {
    throw new TimesheetCannotBeSubmittedException($timesheet->id());
}
```

---

## Доменные исключения

Исключения — часть доменного языка. Каждое несёт смысл.

**Правила:**
- Имя отражает нарушенное бизнес-правило
- Выбрасываются в домене, обрабатываются в инфраструктуре / UI
- Иерархия: базовый `DomainException` → специализированные

```php
// ✅ Базовый класс
abstract class DomainException extends \RuntimeException {}

// ✅ Специализированные
final class TimesheetAlreadyClosedException extends DomainException
{
    public function __construct(TimesheetPeriodId $id)
    {
        parent::__construct("Timesheet {$id->value()} is already closed.");
    }
}

final class OverlappingWorkEntryException extends DomainException
{
    public function __construct(DateRange $period)
    {
        parent::__construct(
            "Work entry overlaps with existing entry in period {$period->start()->format('Y-m-d')}–{$period->end()->format('Y-m-d')}."
        );
    }
}
```

---

## Шпаргалка: что куда класть

| Концепция | Слой | Правило |
|---|---|---|
| Entity, Value Object | Domain | Нет зависимостей от фреймворков |
| Aggregate Root | Domain | Транзакционная граница |
| Repository Interface | Domain | Только контракт |
| Domain Event | Domain | Readonly, только примитивы |
| Domain Service | Domain | Stateless, именован по бизнесу |
| Specification | Domain | Инкапсулирует бизнес-правило |
| Factory | Domain / Application | Нетривиальное создание |
| Command / Query | Application | DTO входных данных |
| Use Case Handler | Application | Оркестрация, без бизнес-логики |
| Repository Impl | Infrastructure | Doctrine / PDO / Redis |
| Anti-Corruption Layer | Infrastructure | Адаптер к внешней системе |
| Controller | UI | Тонкий, без логики |

---

## Антипаттерны

```php
// ❌ Анемичная модель: логика вынесена из сущности
class TimesheetPeriod {
    public TimesheetStatus $status; // публичное поле
}
// И отдельный TimesheetService::submit($timesheet) — бизнес-логика не там

// ❌ "Умный" репозиторий: содержит бизнес-логику
class TimesheetRepository {
    public function submitTimesheet(TimesheetPeriod $t): void {
        $t->status = TimesheetStatus::Submitted; // логика должна быть в агрегате
        $this->em->flush();
    }
}

// ❌ Ссылка на другой агрегат по объекту вместо ID
class WorkEntry {
    private Employee $employee; // ← нарушает границу агрегата
}

// ❌ Возврат внутренних объектов агрегата
class TimesheetPeriod {
    public function getEntries(): array {
        return $this->entries; // ← даёт доступ к внутренностям напрямую
    }
}
```
