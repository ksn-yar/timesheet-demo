---
name: phpunit-tests
description: Правила написания PHPUnit-тестов — структура, именование, паттерны моков, Unit-тесты Value Object / Domain Entity / Use Case, запуск в Docker
---

# PHPUnit: правила и практики

## Ключевые принципы

- **Один класс — один тест-класс**: `CreateManualTicketUseCase` → `CreateManualTicketUseCaseTest`
- **Один тест — один сценарий**: метод проверяет ровно одно поведение
- **Тест-метод описывает поведение**, а не реализацию: `throwsExceptionWhenEmployeeNotFound`, не `testExecuteWithNullEmployee`
- **PHPUnit 10+**: используется атрибут `#[Test]` вместо аннотации `@test`
- **Мокируются только интерфейсы** из Domain/Application слоя — никаких моков конкретных классов
- **Реальные Value Objects** в тестах: создавать через конструктор, не мокировать
- **Реальные доменные исключения**: проверять через `$this->expectException()`

---

## Расположение тестов

```
tests/
├── Unit/
│   ├── {BoundedContext}/
│   │   ├── Domain/
│   │   │   ├── Entity/
│   │   │   │   └── {Entity}Test.php
│   │   │   └── ValueObject/
│   │   │       └── {ValueObject}Test.php
│   │   └── Application/
│   │       └── UseCase/
│   │           └── {Action}{Entity}UseCaseTest.php
│   └── Shared/
└── Integration/
    └── {BoundedContext}/
        └── Infrastructure/
            └── Controller/
                └── {Action}{Entity}ControllerTest.php
```

**Namespace:**

| Путь                                               | Namespace                                               |
|----------------------------------------------------|---------------------------------------------------------|
| `tests/Unit/Timesheet/Domain/ValueObject/`         | `App\Tests\Unit\Timesheet\Domain\ValueObject`           |
| `tests/Unit/Timesheet/Domain/Entity/`              | `App\Tests\Unit\Timesheet\Domain\Entity`                |
| `tests/Unit/Timesheet/Application/UseCase/`        | `App\Tests\Unit\Timesheet\Application\UseCase`          |
| `tests/Integration/Timesheet/Infrastructure/`      | `App\Tests\Integration\Timesheet\Infrastructure`        |

---

## Запуск тестов

```bash
# Все тесты
docker exec -it corpo-ts-backend php bin/phpunit

# Конкретный файл
docker exec -it corpo-ts-backend php bin/phpunit tests/Unit/Reporting/Domain/ValueObject/ReportPeriodTest.php

# Конкретный тест-метод
docker exec -it corpo-ts-backend php bin/phpunit --filter throwsExceptionWhenPeriodIsInvalid

# Только Unit-тесты
docker exec -it corpo-ts-backend php bin/phpunit tests/Unit
```

---

## 1. Тест Value Object

Value Object проверяет инварианты (бизнес-правила), установленные в конструкторе, и корректность геттеров.

### Шаблон

```php
// tests/Unit/{BoundedContext}/Domain/ValueObject/{ValueObject}Test.php

declare(strict_types=1);

namespace App\Tests\Unit\{BoundedContext}\Domain\ValueObject;

use App\{BoundedContext}\Domain\ValueObject\{ValueObject};
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет инварианты Value Object {ValueObject}. */
final class {ValueObject}Test extends TestCase
{
    #[Test]
    public function createsWithValidData(): void
    {
        $vo = new {ValueObject}(/* валидные данные */);

        self::assertSame(/* ожидаемое значение */, $vo->someGetter());
    }

    #[Test]
    public function throwsExceptionWhen{InvalidCondition}(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new {ValueObject}(/* невалидные данные */);
    }
}
```

### Конкретный пример: ReportPeriod

```php
// tests/Unit/Reporting/Domain/ValueObject/ReportPeriodTest.php

declare(strict_types=1);

namespace App\Tests\Unit\Reporting\Domain\ValueObject;

use App\Reporting\Domain\Exception\InvalidReportPeriodException;
use App\Reporting\Domain\ValueObject\ReportPeriod;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет инварианты периода отчёта: дата начала не может превышать дату окончания. */
final class ReportPeriodTest extends TestCase
{
    #[Test]
    public function createsWithValidDates(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to   = new DateTimeImmutable('2026-01-31');

        $period = new ReportPeriod($from, $to);

        self::assertSame($from, $period->from());
        self::assertSame($to, $period->to());
    }

    #[Test]
    public function createsWhenFromEqualsTo(): void
    {
        $date = new DateTimeImmutable('2026-06-15');

        $period = new ReportPeriod($date, $date);

        self::assertSame($date, $period->from());
    }

    #[Test]
    public function throwsExceptionWhenFromExceedsTo(): void
    {
        $this->expectException(InvalidReportPeriodException::class);

        new ReportPeriod(
            new DateTimeImmutable('2026-02-01'),
            new DateTimeImmutable('2026-01-01'),
        );
    }
}
```

### Конкретный пример: ImportLogEntry (с условной валидацией)

```php
// tests/Unit/Timesheet/Domain/ValueObject/ImportLogEntryTest.php

declare(strict_types=1);

namespace App\Tests\Unit\Timesheet\Domain\ValueObject;

use App\Timesheet\Domain\Enum\ImportLogEntryStatus;
use App\Timesheet\Domain\ValueObject\ImportLogEntry;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет инварианты записи лога импорта. */
final class ImportLogEntryTest extends TestCase
{
    #[Test]
    public function createsSuccessEntry(): void
    {
        $entry = new ImportLogEntry('ext-123', ImportLogEntryStatus::Success);

        self::assertSame('ext-123', $entry->externalId());
        self::assertSame(ImportLogEntryStatus::Success, $entry->status());
        self::assertNull($entry->errorReason());
    }

    #[Test]
    public function createsErrorEntryWithReason(): void
    {
        $entry = new ImportLogEntry('ext-456', ImportLogEntryStatus::Error, 'Дублирующийся внешний ID.');

        self::assertSame('Дублирующийся внешний ID.', $entry->errorReason());
    }

    #[Test]
    public function throwsExceptionWhenErrorReasonMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Причина ошибки обязательна');

        new ImportLogEntry('ext-789', ImportLogEntryStatus::Error);
    }
}
```

---

## 2. Тест Domain Entity

Domain Entity проверяет бизнес-логику фабричных методов, изменений состояния и доменных событий.

### Шаблон

```php
// tests/Unit/{BoundedContext}/Domain/Entity/{Entity}Test.php

declare(strict_types=1);

namespace App\Tests\Unit\{BoundedContext}\Domain\Entity;

use App\{BoundedContext}\Domain\Entity\{Entity};
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет бизнес-правила агрегата {Entity}. */
final class {Entity}Test extends TestCase
{
    #[Test]
    public function creates{Entity}WithValidData(): void
    {
        $entity = {Entity}::create(/* параметры */);

        self::assertSame(/* ожидание */, $entity->someGetter());
    }

    #[Test]
    public function recordsDomainEventOnCreate(): void
    {
        $entity = {Entity}::create(/* параметры */);

        $events = $entity->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf({ExpectedEvent}::class, $events[0]);
    }

    #[Test]
    public function throwsExceptionWhen{BusinessRule}Violated(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        {Entity}::create(/* невалидные данные */);
    }
}
```

### Конкретный пример: ChangeRequest

```php
// tests/Unit/ProjectManagement/Domain/Entity/ChangeRequestTest.php

declare(strict_types=1);

namespace App\Tests\Unit\ProjectManagement\Domain\Entity;

use App\ProjectManagement\Domain\Entity\ChangeRequest;
use App\ProjectManagement\Domain\Event\ChangeRequestCreated;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет бизнес-правила агрегата ChangeRequest. */
final class ChangeRequestTest extends TestCase
{
    private const PROJECT_ID = '550e8400-e29b-41d4-a716-446655440001';

    #[Test]
    public function createsChangeRequestWithValidData(): void
    {
        $cr = ChangeRequest::create(self::PROJECT_ID, 'Рефакторинг модуля', null);

        self::assertSame('Рефакторинг модуля', $cr->getName());
        self::assertNull($cr->getDescription());
        self::assertFalse($cr->isDeleted());
    }

    #[Test]
    public function recordsCreatedEventOnCreate(): void
    {
        $cr = ChangeRequest::create(self::PROJECT_ID, 'Новый CR', 'Описание');

        $events = $cr->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(ChangeRequestCreated::class, $events[0]);
    }

    #[Test]
    public function pullDomainEventsClearsEventList(): void
    {
        $cr = ChangeRequest::create(self::PROJECT_ID, 'CR', null);

        $cr->pullDomainEvents();

        self::assertEmpty($cr->pullDomainEvents());
    }

    #[Test]
    public function throwsExceptionWhenNameIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ChangeRequest::create(self::PROJECT_ID, '  ', null);
    }
}
```

---

## 3. Тест Use Case (Command)

Command Use Case проверяет: вызовы репозитория, порядок вызовов, выброс исключений.

### Шаблон

```php
// tests/Unit/{BoundedContext}/Application/UseCase/{Action}{Entity}UseCaseTest.php

declare(strict_types=1);

namespace App\Tests\Unit\{BoundedContext}\Application\UseCase;

use App\{BoundedContext}\Application\Dto\{Action}{Entity}InputDto;
use App\{BoundedContext}\Application\UseCase\{Action}{Entity}UseCase;
use App\{BoundedContext}\Domain\Repository\{Entity}RepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет сценарий {действие} {сущности}. */
final class {Action}{Entity}UseCaseTest extends TestCase
{
    private {Entity}RepositoryInterface $repository;
    private {Action}{Entity}UseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock({Entity}RepositoryInterface::class);
        $this->useCase    = new {Action}{Entity}UseCase($this->repository);
    }

    #[Test]
    public function saves{Entity}WhenDataIsValid(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf({Entity}::class));

        $this->useCase->execute(new {Action}{Entity}InputDto(/* поля */));
    }

    #[Test]
    public function throwsExceptionWhen{Entity}NotFound(): void
    {
        $this->repository
            ->method('findById')
            ->willReturn(null);

        $this->repository
            ->expects(self::never())
            ->method('save');

        $this->expectException({Entity}NotFoundException::class);

        $this->useCase->execute(new {Action}{Entity}InputDto(/* поля */));
    }
}
```

### Конкретный пример: CreateManualTicketUseCase

```php
// tests/Unit/Timesheet/Application/UseCase/CreateManualTicketUseCaseTest.php

declare(strict_types=1);

namespace App\Tests\Unit\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\CreateManualTicketInputDto;
use App\Timesheet\Application\Port\CurrentUserProviderInterface;
use App\Timesheet\Application\Port\RateProviderInterface;
use App\Timesheet\Application\Port\TaskExistenceCheckerInterface;
use App\Timesheet\Application\Port\WorkExistenceCheckerInterface;
use App\Timesheet\Application\UseCase\CreateManualTicketUseCase;
use App\Timesheet\Domain\Entity\Ticket;
use App\Timesheet\Domain\Exception\TicketOwnershipViolationException;
use App\Timesheet\Domain\Repository\TicketRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Проверяет сценарий создания тикета вручную. */
final class CreateManualTicketUseCaseTest extends TestCase
{
    private TicketRepositoryInterface $ticketRepository;
    private TaskExistenceCheckerInterface $taskExistenceChecker;
    private WorkExistenceCheckerInterface $workExistenceChecker;
    private RateProviderInterface $rateProvider;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;
    private CreateManualTicketUseCase $useCase;

    protected function setUp(): void
    {
        $this->ticketRepository     = $this->createMock(TicketRepositoryInterface::class);
        $this->taskExistenceChecker = $this->createMock(TaskExistenceCheckerInterface::class);
        $this->workExistenceChecker = $this->createMock(WorkExistenceCheckerInterface::class);
        $this->rateProvider         = $this->createMock(RateProviderInterface::class);
        $this->currentUserProvider  = $this->createMock(CurrentUserProviderInterface::class);
        $this->eventDispatcher      = $this->createMock(EventDispatcherInterface::class);

        $this->useCase = new CreateManualTicketUseCase(
            $this->ticketRepository,
            $this->taskExistenceChecker,
            $this->workExistenceChecker,
            $this->rateProvider,
            $this->currentUserProvider,
            $this->eventDispatcher,
        );
    }

    #[Test]
    public function savesTicketWhenAdminCreatesForAnyEmployee(): void
    {
        $this->currentUserProvider->method('isAdmin')->willReturn(true);
        $this->taskExistenceChecker->method('taskExists')->willReturn(true);
        $this->workExistenceChecker->method('workExists')->willReturn(true);
        $this->rateProvider->method('getCurrentRate')->willReturn(null);

        $this->ticketRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(Ticket::class));

        $this->useCase->execute($this->buildValidInput());
    }

    #[Test]
    public function savesTicketWhenEmployeeCreatesForSelf(): void
    {
        $employeeId = 'emp-uuid-001';

        $this->currentUserProvider->method('isAdmin')->willReturn(false);
        $this->currentUserProvider->method('getCurrentUserId')->willReturn($employeeId);
        $this->taskExistenceChecker->method('taskExists')->willReturn(true);
        $this->workExistenceChecker->method('workExists')->willReturn(true);
        $this->rateProvider->method('getCurrentRate')->willReturn(null);

        $this->ticketRepository->expects(self::once())->method('save');

        $this->useCase->execute($this->buildValidInput(employeeId: $employeeId));
    }

    #[Test]
    public function throwsExceptionWhenEmployeeCreatesForAnotherEmployee(): void
    {
        $this->currentUserProvider->method('isAdmin')->willReturn(false);
        $this->currentUserProvider->method('getCurrentUserId')->willReturn('emp-uuid-001');

        $this->ticketRepository->expects(self::never())->method('save');

        $this->expectException(TicketOwnershipViolationException::class);

        $this->useCase->execute($this->buildValidInput(employeeId: 'emp-uuid-999'));
    }

    #[Test]
    public function throwsExceptionWhenTaskNotFound(): void
    {
        $this->currentUserProvider->method('isAdmin')->willReturn(true);
        $this->taskExistenceChecker->method('taskExists')->willReturn(false);

        $this->ticketRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->useCase->execute($this->buildValidInput());
    }

    #[Test]
    public function dispatchesDomainEventsAfterSave(): void
    {
        $this->currentUserProvider->method('isAdmin')->willReturn(true);
        $this->taskExistenceChecker->method('taskExists')->willReturn(true);
        $this->workExistenceChecker->method('workExists')->willReturn(true);
        $this->rateProvider->method('getCurrentRate')->willReturn(null);

        $this->eventDispatcher
            ->expects(self::atLeastOnce())
            ->method('dispatch');

        $this->useCase->execute($this->buildValidInput());
    }

    private function buildValidInput(string $employeeId = 'emp-uuid-001'): CreateManualTicketInputDto
    {
        return new CreateManualTicketInputDto(
            employeeId: $employeeId,
            taskId:     'task-uuid-001',
            workId:     'work-uuid-001',
            date:       '2026-05-01',
            hours:      8.0,
            comment:    null,
        );
    }
}
```

---

## 4. Тест Use Case (Query)

Query Use Case проверяет вызов `present()` с корректным OutputDto и выброс исключений.

### Шаблон

```php
// tests/Unit/{BoundedContext}/Application/UseCase/Get{Entity}UseCaseTest.php

declare(strict_types=1);

namespace App\Tests\Unit\{BoundedContext}\Application\UseCase;

use App\{BoundedContext}\Application\Dto\Get{Entity}InputDto;
use App\{BoundedContext}\Application\Dto\Get{Entity}OutputDto;
use App\{BoundedContext}\Application\Port\Get{Entity}OutputPortInterface;
use App\{BoundedContext}\Application\UseCase\Get{Entity}UseCase;
use App\{BoundedContext}\Domain\Entity\{Entity};
use App\{BoundedContext}\Domain\Exception\{Entity}NotFoundException;
use App\{BoundedContext}\Domain\Repository\{Entity}RepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет сценарий получения {сущности}. */
final class Get{Entity}UseCaseTest extends TestCase
{
    private {Entity}RepositoryInterface $repository;
    private Get{Entity}OutputPortInterface $presenter;
    private Get{Entity}UseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock({Entity}RepositoryInterface::class);
        $this->presenter  = $this->createMock(Get{Entity}OutputPortInterface::class);
        $this->useCase    = new Get{Entity}UseCase($this->repository, $this->presenter);
    }

    #[Test]
    public function presents{Entity}WhenFound(): void
    {
        $entity = $this->createMock({Entity}::class);
        // настройка геттеров сущности...

        $this->repository->method('findById')->willReturn($entity);

        $this->presenter
            ->expects(self::once())
            ->method('present')
            ->with(self::isInstanceOf(Get{Entity}OutputDto::class));

        $this->useCase->execute(new Get{Entity}InputDto(id: 'uuid-here'));
    }

    #[Test]
    public function throwsExceptionWhen{Entity}NotFound(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->presenter->expects(self::never())->method('present');

        $this->expectException({Entity}NotFoundException::class);

        $this->useCase->execute(new Get{Entity}InputDto(id: 'uuid-here'));
    }
}
```

---

## 5. Паттерны моков

### createMock — мок интерфейса

```php
// Все методы возвращают null/false/0 по умолчанию
$repository = $this->createMock(TicketRepositoryInterface::class);

// Настройка возвращаемого значения
$repository->method('findById')->willReturn($ticket);

// Проверка вызова ровно один раз
$repository->expects(self::once())->method('save');

// Проверка, что метод не вызывался
$repository->expects(self::never())->method('save');

// Проверка аргумента
$repository
    ->expects(self::once())
    ->method('save')
    ->with(self::isInstanceOf(Ticket::class));
```

### createConfiguredMock — мок с предустановленными геттерами

```php
// Когда нужно настроить несколько методов сразу
$ticket = $this->createConfiguredMock(Ticket::class, [
    'getId'         => new TicketId('550e8400-e29b-41d4-a716-446655440000'),
    'getEmployeeId' => 'emp-uuid-001',
]);
```

### Цепочка вызовов willReturnCallback

```php
// Когда поведение зависит от аргументов
$checker->method('taskExists')
    ->willReturnCallback(fn(string $id) => $id !== 'nonexistent-task');
```

### consecutive — разные возвращаемые значения

```php
// PHPUnit 10+: использовать willReturnOnConsecutiveCalls
$repository->method('findById')
    ->willReturnOnConsecutiveCalls($ticket1, $ticket2, null);
```

---

## 6. Именование тест-методов

Имя метода описывает **поведение** в виде `{что происходит}When{условие}` или `{глагол}{что}`:

| Паттерн                                        | Пример                                              |
|------------------------------------------------|-----------------------------------------------------|
| Успешный сценарий, без условия                 | `createsTicketWithValidData`                        |
| Успешный сценарий, с условием                  | `savesTicketWhenAdminCreatesForAnyEmployee`          |
| Исключение при нарушении правила               | `throwsExceptionWhenTaskNotFound`                   |
| Исключение при нарушении инварианта            | `throwsExceptionWhenFromExceedsTo`                  |
| Проверка отсутствия вызова                     | `doesNotSaveWhenOwnershipViolated`                  |
| Проверка побочного эффекта                     | `dispatchesDomainEventsAfterSave`                   |
| Проверка состояния после действия              | `pullDomainEventsClearsEventList`                   |

---

## 7. Вспомогательные методы

Выносить повторяющиеся данные в `private` методы внутри тест-класса:

```php
private function buildValidInput(string $employeeId = 'emp-uuid-001'): CreateManualTicketInputDto
{
    return new CreateManualTicketInputDto(
        employeeId: $employeeId,
        taskId:     'task-uuid-001',
        workId:     'work-uuid-001',
        date:       '2026-05-01',
        hours:      8.0,
        comment:    null,
    );
}
```

Вспомогательные методы всегда `private`, без `#[Test]`, с описательным именем.

---

## 8. Структура тест-класса

```php
declare(strict_types=1);

namespace App\Tests\Unit\{BoundedContext}\...;

use ...;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Краткое описание того, что проверяется. */
final class {Subject}Test extends TestCase
{
    // 1. Поля — моки и тестируемый объект
    private SomeDependencyInterface $dependency;
    private SubjectUnderTest $subject;

    // 2. setUp — создание моков и SUT
    protected function setUp(): void
    {
        $this->dependency = $this->createMock(SomeDependencyInterface::class);
        $this->subject    = new SubjectUnderTest($this->dependency);
    }

    // 3. Тесты — #[Test], descriptive names
    #[Test]
    public function doesSomethingWhenConditionMet(): void
    {
        // Arrange
        $this->dependency->method('find')->willReturn(/* ... */);

        // Assert (перед Act если expectException)
        // $this->expectException(SomeException::class);

        // Act
        $this->subject->execute(/* ... */);

        // Assert
        self::assertSame(/* ... */);
    }

    // 4. Вспомогательные методы последними
    private function buildInput(/* ... */): SomeInputDto { ... }
}
```

---

## Чек-лист при написании теста

- [ ] Тест размещён в `tests/Unit/{BoundedContext}/...` (зеркально `src/`)
- [ ] Класс объявлен как `final class {Subject}Test extends TestCase`
- [ ] Используется `#[Test]` атрибут (не `@test` аннотация)
- [ ] Имя метода описывает поведение, а не реализацию
- [ ] Мокируются только интерфейсы (`RepositoryInterface`, `OutputPortInterface`, `ProviderInterface`)
- [ ] Value Objects создаются реальными (`new ReportPeriod(...)`, не `createMock(ReportPeriod::class)`)
- [ ] Каждый тест проверяет ровно один сценарий
- [ ] `setUp()` содержит только инициализацию моков и SUT
- [ ] Повторяющиеся входные данные вынесены в `private` helper-методы
- [ ] `expects(self::never())` используется явно, когда нежелательный вызов важен для теста
- [ ] `$this->expectException()` вызывается до `execute()`, а не после

---

## Антипаттерны

```php
// ❌ Аннотация @test вместо атрибута #[Test]
/** @test */
public function createsTicket(): void { ... }
// Используйте: #[Test]

// ❌ Имя метода начинается с test
public function testExecute(): void { ... }
// Используйте: #[Test] + описательное имя createsTicketWithValidData

// ❌ Мок конкретного класса вместо интерфейса
$repository = $this->createMock(DoctrineTicketRepository::class);
// Используйте: $this->createMock(TicketRepositoryInterface::class)

// ❌ Мок Value Object
$ticketId = $this->createMock(TicketId::class);
// Используйте: new TicketId('550e8400-e29b-41d4-a716-446655440000')

// ❌ Несколько сценариев в одном тесте
#[Test]
public function testTicketCreationAndDeletion(): void
{
    // создание...
    // удаление...
    // Разбейте на два независимых теста
}

// ❌ expectException после вызова метода
$this->useCase->execute($input);
$this->expectException(DomainException::class); // никогда не сработает
// Используйте expectException ДО вызова метода

// ❌ Тест без Assert
#[Test]
public function createsTicket(): void
{
    $this->useCase->execute($this->buildValidInput());
    // Нет проверки — либо добавьте expects(), либо self::assert*()
}

// ❌ Логика в setUp, специфичная для одного теста
protected function setUp(): void
{
    $this->repository->method('findById')->willReturn($this->ticket); // настройка только для одного теста
    // Перенесите настройку мока в конкретный тест-метод
}
```
