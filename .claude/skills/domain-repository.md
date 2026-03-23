---
name: domain-repository
description: Правила создания доменного репозитория — интерфейс в Domain-слое, реализация в Infrastructure через composition с Doctrine Repository из Persistence-домена
---

# Доменный репозиторий: правила и практики

## Ключевые принципы

- **Интерфейс репозитория** определяется в доменном слое Bounded Context и не зависит от инфраструктуры
- **Реализация репозитория** находится в инфраструктурном слое и использует **composition** (не наследование) с Doctrine Repository из домена `Persistence`
- **Репозиторий существует только для Aggregate Root** — не для внутренних Entity агрегата
- **Один репозиторий — один агрегат**
- **Доменный интерфейс оперирует доменными типами** (Value Objects, Domain Entity) — не Doctrine Entity и не примитивами
- **Реализация выполняет маппинг** между доменными объектами и Doctrine Entity из `Persistence`

---

## Архитектурное расположение

```
src/
├── Persistence/                          <-- единый Doctrine-домен (см. skill doctrine-orm-entity-repository)
│   ├── Entity/
│   │   └── WorkEntry.php                 # Doctrine Entity (примитивные типы, ORM-маппинг)
│   └── Repository/
│       └── WorkEntryRepository.php       # ServiceEntityRepository<WorkEntry>
│
├── Timesheet/                            <-- бизнес-домен (Bounded Context)
│   ├── Domain/
│   │   ├── Entity/
│   │   │   └── WorkEntry.php             # Domain Entity / Aggregate Root (Value Objects, инварианты)
│   │   ├── ValueObject/
│   │   │   ├── WorkEntryId.php
│   │   │   └── Hours.php
│   │   └── Repository/
│   │       └── WorkEntryRepositoryInterface.php   # доменный интерфейс
│   └── Infrastructure/
│       └── Repository/
│           └── WorkEntryRepository.php     # реализация через composition
```

**Почему так:**
- Интерфейс в `Domain/Repository/` — Dependency Inversion Principle: домен задаёт контракт, инфраструктура реализует
- Реализация в `Infrastructure/Repository/` — единственный слой, которому разрешено зависеть от Doctrine
- Doctrine Entity и Repository живут в `Persistence/` — архитектурный компромисс проекта (см. skill `doctrine-orm-entity-repository`)
- Бизнес-домен не обращается к `Persistence` напрямую — реализация репозитория выступает как ACL (Anti-Corruption Layer)

---

## 1. Интерфейс репозитория (Domain слой)

Интерфейс определяет контракт доступа к агрегату на языке домена. Не зависит от инфраструктуры.

```php
// src/{BoundedContext}/Domain/Repository/{Entity}RepositoryInterface.php

declare(strict_types=1);

namespace App\{BoundedContext}\Domain\Repository;

use App\{BoundedContext}\Domain\Entity\{Entity};
use App\{BoundedContext}\Domain\ValueObject\{Entity}Id;

/** Контракт хранилища агрегатов {Entity}. Определяет доменные операции доступа к данным. */
interface {Entity}RepositoryInterface
{
    public function save({Entity} $entity): void;

    public function findById({Entity}Id $id): ?{Entity};

    public function remove({Entity} $entity): void;
}
```

### Конкретный пример

```php
// src/Timesheet/Domain/Repository/WorkEntryRepositoryInterface.php

declare(strict_types=1);

namespace App\Timesheet\Domain\Repository;

use App\Timesheet\Domain\Entity\WorkEntry;
use App\Timesheet\Domain\ValueObject\EmployeeId;
use App\Timesheet\Domain\ValueObject\WorkEntryId;

/** Контракт хранилища агрегатов WorkEntry. Определяет доменные операции доступа к данным. */
interface WorkEntryRepositoryInterface
{
    public function save(WorkEntry $workEntry): void;

    public function findById(WorkEntryId $id): ?WorkEntry;

    /** @return WorkEntry[] */
    public function findByEmployee(EmployeeId $employeeId): array;

    public function remove(WorkEntry $workEntry): void;
}
```

### Правила для интерфейса

- **Только доменные типы** в сигнатурах: Value Objects (`WorkEntryId`, `EmployeeId`), Domain Entity (`WorkEntry`)
- **Запрещено**: `use Doctrine\...`, `use Symfony\...`, примитивные типы вместо Value Objects для ID
- **Именование методов** отражает бизнес-намерение: `save`, `findById`, `findByEmployee`, `remove`
- **Не возвращать коллекции Doctrine** (`Collection`, `ArrayCollection`) — только `array`
- **Фильтрующие методы** принимают Value Objects: `findByEmployee(EmployeeId $id)`, не `findByEmployee(string $id)`

---

## 2. Реализация репозитория (Infrastructure слой)

Реализация внедряет Doctrine Repository из `Persistence` через конструктор (composition) и выполняет маппинг между доменными объектами и Doctrine Entity.

```php
// src/{BoundedContext}/Infrastructure/Repository/{Entity}Repository.php

declare(strict_types=1);

namespace App\{BoundedContext}\Infrastructure\Repository;

use App\{BoundedContext}\Domain\Entity\{Entity};
use App\{BoundedContext}\Domain\Repository\{Entity}RepositoryInterface;
use App\{BoundedContext}\Domain\ValueObject\{Entity}Id;
use App\Persistence\Entity\{Entity} as {Entity}OrmEntity;
use App\Persistence\Repository\{Entity}Repository as {Entity}OrmRepository;

/**
 * Doctrine-реализация хранилища {Entity}.
 * Использует composition с Doctrine Repository из Persistence-домена.
 */
final class {Entity}Repository implements {Entity}RepositoryInterface
{
    public function __construct(
        private readonly {Entity}OrmRepository $ormRepository,
    ) {}

    public function save({Entity} $entity): void
    {
        $ormEntity = $this->toOrmEntity($entity);
        $this->ormRepository->save($ormEntity, flush: true);
    }

    public function findById({Entity}Id $id): ?{Entity}
    {
        $ormEntity = $this->ormRepository->find($id->value());

        if ($ormEntity === null) {
            return null;
        }

        return $this->toDomainEntity($ormEntity);
    }

    public function remove({Entity} $entity): void
    {
        $ormEntity = $this->ormRepository->find($entity->id()->value());

        if ($ormEntity !== null) {
            $this->ormRepository->remove($ormEntity, flush: true);
        }
    }

    /** Преобразует доменную сущность в Doctrine Entity. */
    private function toOrmEntity({Entity} $entity): {Entity}OrmEntity
    {
        // Маппинг Value Objects -> примитивы для ORM Entity
        $ormEntity = new {Entity}OrmEntity();
        // $ormEntity->setField($entity->field()->value());
        // ...

        return $ormEntity;
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity({Entity}OrmEntity $ormEntity): {Entity}
    {
        // Маппинг примитивов ORM Entity -> Value Objects и восстановление доменной сущности
        // return {Entity}::restore(
        //     new {Entity}Id($ormEntity->getId()),
        //     ...
        // );
    }
}
```

### Конкретный пример

```php
// src/Timesheet/Infrastructure/Repository/WorkEntryRepository.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Repository;

use App\Persistence\Entity\WorkEntry as WorkEntryOrmEntity;
use App\Persistence\Repository\WorkEntryRepository as WorkEntryOrmRepository;
use App\Timesheet\Domain\Entity\WorkEntry;
use App\Timesheet\Domain\Repository\WorkEntryRepositoryInterface;
use App\Timesheet\Domain\ValueObject\EmployeeId;
use App\Timesheet\Domain\ValueObject\Hours;
use App\Timesheet\Domain\ValueObject\WorkEntryId;
use App\Timesheet\Domain\ValueObject\DateRange;

/**
 * Doctrine-реализация хранилища WorkEntry.
 * Использует composition с Doctrine Repository из Persistence-домена.
 */
final class WorkEntryRepository implements WorkEntryRepositoryInterface
{
    public function __construct(
        private readonly WorkEntryOrmRepository $ormRepository,
    ) {}

    public function save(WorkEntry $workEntry): void
    {
        $ormEntity = $this->toOrmEntity($workEntry);
        $this->ormRepository->save($ormEntity, flush: true);
    }

    public function findById(WorkEntryId $id): ?WorkEntry
    {
        $ormEntity = $this->ormRepository->find($id->value());

        if ($ormEntity === null) {
            return null;
        }

        return $this->toDomainEntity($ormEntity);
    }

    /** @return WorkEntry[] */
    public function findByEmployee(EmployeeId $employeeId): array
    {
        $ormEntities = $this->ormRepository->findByEmployee($employeeId->value());

        return array_map(
            fn(WorkEntryOrmEntity $ormEntity): WorkEntry => $this->toDomainEntity($ormEntity),
            $ormEntities,
        );
    }

    public function remove(WorkEntry $workEntry): void
    {
        $ormEntity = $this->ormRepository->find($workEntry->id()->value());

        if ($ormEntity !== null) {
            $this->ormRepository->remove($ormEntity, flush: true);
        }
    }

    /** Преобразует доменную сущность в Doctrine Entity. */
    private function toOrmEntity(WorkEntry $workEntry): WorkEntryOrmEntity
    {
        $ormEntity = new WorkEntryOrmEntity();
        $ormEntity->setEmployeeId($workEntry->employeeId()->value());
        $ormEntity->setStartDate($workEntry->period()->start());
        $ormEntity->setEndDate($workEntry->period()->end());
        $ormEntity->setHours($workEntry->hours()->value());
        $ormEntity->setStatus($workEntry->status());
        $ormEntity->setCreatedAt($workEntry->createdAt());

        return $ormEntity;
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity(WorkEntryOrmEntity $ormEntity): WorkEntry
    {
        return WorkEntry::restore(
            new WorkEntryId($ormEntity->getId()),
            new EmployeeId($ormEntity->getEmployeeId()),
            DateRange::fromStrings(
                $ormEntity->getStartDate()->format('Y-m-d'),
                $ormEntity->getEndDate()->format('Y-m-d'),
            ),
            new Hours((float) $ormEntity->getHours()),
        );
    }
}
```

---

## 3. Регистрация в DI-контейнере

При стандартной конфигурации (autowire + autoconfigure включены, `App\\` сканируется из `src/`) Symfony автоматически обнаружит `WorkEntryRepository` и внедрит в него `WorkEntryOrmRepository`.

Для привязки интерфейса к реализации требуется явный биндинг в конфигурации сервисов.

### Вариант 1: через services.php (рекомендуется)

```php
// config/services.php

use App\Timesheet\Domain\Repository\WorkEntryRepositoryInterface;
use App\Timesheet\Infrastructure\Repository\WorkEntryRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(WorkEntryRepositoryInterface::class, WorkEntryRepository::class);
};
```

### Вариант 2: через атрибут #[AutoconfigureTag] + #[AsAlias]

```php
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkEntryRepositoryInterface::class)]
final class WorkEntryRepository implements WorkEntryRepositoryInterface
{
    // ...
}
```

---

## 4. Маппинг: доменные объекты <-> Doctrine Entity

Реализация репозитория выполняет роль Anti-Corruption Layer — преобразует между двумя моделями.

### Направления маппинга

| Операция                         | Направление                            | Метод              |
|----------------------------------|----------------------------------------|--------------------|
| `save()`                         | Domain Entity -> Doctrine Entity       | `toOrmEntity()`    |
| `findById()`, `findByEmployee()` | Doctrine Entity -> Domain Entity       | `toDomainEntity()` |
| `remove()`                       | Domain Entity -> ID -> Doctrine Entity | lookup по ID       |

### Правила маппинга

- **`toOrmEntity()`** — извлекает примитивные значения из Value Objects через `.value()`, `.start()` и т.д.
- **`toDomainEntity()`** — создаёт Value Objects из примитивов и восстанавливает доменную сущность через фабричный метод `restore()`
- **Обновление существующей записи** — при `save()` для уже существующей сущности найти ORM Entity по ID, обновить поля, вызвать `flush`:

```php
public function save(WorkEntry $workEntry): void
{
    $existingOrmEntity = $this->ormRepository->find($workEntry->id()->value());

    if ($existingOrmEntity !== null) {
        $this->updateOrmEntity($existingOrmEntity, $workEntry);
        $this->ormRepository->save($existingOrmEntity, flush: true);
        return;
    }

    $ormEntity = $this->toOrmEntity($workEntry);
    $this->ormRepository->save($ormEntity, flush: true);
}
```

---

## 5. Unit-тест реализации репозитория

```php
// tests/Unit/{BoundedContext}/Infrastructure/Repository/{Entity}RepositoryTest.php

declare(strict_types=1);

namespace App\Tests\Unit\{BoundedContext}\Infrastructure\Repository;

use App\{BoundedContext}\Domain\Entity\{Entity};
use App\{BoundedContext}\Domain\ValueObject\{Entity}Id;
use App\{BoundedContext}\Infrastructure\Repository\{Entity}Repository;
use App\Persistence\Entity\{Entity} as {Entity}OrmEntity;
use App\Persistence\Repository\{Entity}Repository as {Entity}OrmRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет маппинг и делегирование вызовов в Doctrine Repository. */
final class {Entity}RepositoryTest extends TestCase
{
    private {Entity}OrmRepository $ormRepository;
    private {Entity}Repository $repository;

    protected function setUp(): void
    {
        $this->ormRepository = $this->createMock({Entity}OrmRepository::class);
        $this->repository = new {Entity}Repository($this->ormRepository);
    }

    #[Test]
    public function saveDelegatesToOrmRepository(): void
    {
        $domainEntity = $this->createDomainEntity();

        $this->ormRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                self::callback(fn({Entity}OrmEntity $orm): bool =>
                    // Проверяем, что маппинг выполнен корректно
                    $orm->getFieldA() === 'expected_value'
                ),
                true,
            );

        $this->repository->save($domainEntity);
    }

    #[Test]
    public function findByIdReturnsNullWhenNotFound(): void
    {
        $this->ormRepository
            ->method('find')
            ->willReturn(null);

        $result = $this->repository->findById(new {Entity}Id('550e8400-e29b-41d4-a716-446655440000'));

        self::assertNull($result);
    }

    #[Test]
    public function findByIdReturnsMappedDomainEntity(): void
    {
        $ormEntity = $this->createOrmEntity();

        $this->ormRepository
            ->method('find')
            ->willReturn($ormEntity);

        $result = $this->repository->findById(new {Entity}Id('550e8400-e29b-41d4-a716-446655440000'));

        self::assertNotNull($result);
        self::assertInstanceOf({Entity}::class, $result);
        // Дополнительные проверки маппинга полей
    }

    private function createDomainEntity(): {Entity}
    {
        // Создать доменную сущность через фабричный метод
    }

    private function createOrmEntity(): {Entity}OrmEntity
    {
        // Создать Doctrine Entity с тестовыми данными
    }
}
```

---

## Чек-лист при создании доменного репозитория

### Интерфейс (Domain)

- [ ] Интерфейс размещён в `src/{BoundedContext}/Domain/Repository/`
- [ ] Именование: `{Entity}RepositoryInterface`
- [ ] Методы принимают и возвращают только доменные типы (Value Objects, Domain Entity)
- [ ] Нет зависимостей от Doctrine, Symfony или любой инфраструктуры
- [ ] Нет `use Doctrine\...` или `use Symfony\...`
- [ ] Методы именованы по бизнес-намерению (`save`, `findById`, `findByEmployee`)
- [ ] Возвращает `array`, не `Collection`
- [ ] PHPDoc-комментарий на интерфейсе описывает контракт

### Реализация (Infrastructure)

- [ ] Реализация размещена в `src/{BoundedContext}/Infrastructure/Repository/`
- [ ] Именование: `{Entity}Repository`
- [ ] Implements доменный интерфейс `{Entity}RepositoryInterface`
- [ ] Класс объявлен как `final`
- [ ] Doctrine Repository из `Persistence` внедряется через конструктор (**composition**)
- [ ] Не наследует `ServiceEntityRepository` или `EntityRepository`
- [ ] Метод `toOrmEntity()` — маппинг Domain Entity -> Doctrine Entity
- [ ] Метод `toDomainEntity()` — маппинг Doctrine Entity -> Domain Entity через `restore()`
- [ ] `save()` обрабатывает и создание, и обновление (lookup по ID)
- [ ] `remove()` ищет ORM Entity по ID перед удалением
- [ ] PHPDoc-комментарий на классе описывает реализацию

### DI-контейнер

- [ ] Интерфейс привязан к реализации (alias в `services.php` или `#[AsAlias]`)
- [ ] Doctrine Repository из `Persistence` автоматически внедряется через autowire

### Тесты

- [ ] Unit-тест проверяет маппинг Domain Entity <-> Doctrine Entity
- [ ] Unit-тест проверяет делегирование вызовов в ORM Repository
- [ ] Unit-тест проверяет возврат `null` при отсутствии записи

---

## Антипаттерны

```php
// -- Наследование от ServiceEntityRepository -- нарушает composition over inheritance
final class WorkEntryRepository extends ServiceEntityRepository
    implements WorkEntryRepositoryInterface
{
    // Наследование связывает класс с Doctrine навсегда.
    // Используйте composition: внедрите ORM Repository через конструктор.
}

// -- Прямое использование EntityManagerInterface вместо ORM Repository из Persistence
final class WorkEntryRepository implements WorkEntryRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em, // используйте WorkEntryOrmRepository
    ) {}
}
// ORM Repository из Persistence уже инкапсулирует EntityManager.
// Повторное обращение к EntityManager дублирует ответственность.

// -- Возврат Doctrine Entity из доменного репозитория
interface WorkEntryRepositoryInterface
{
    public function findById(string $id): ?\App\Persistence\Entity\WorkEntry;
    // Доменный интерфейс не должен знать о Persistence-слое.
    // Возвращайте Domain Entity.
}

// -- Примитивные типы вместо Value Objects в сигнатуре интерфейса
interface WorkEntryRepositoryInterface
{
    public function findById(string $id): ?WorkEntry;      // string вместо WorkEntryId
    public function findByEmployee(string $empId): array;   // string вместо EmployeeId
    // Используйте Value Objects для типобезопасности.
}

// -- Инфраструктурные зависимости в доменном интерфейсе
namespace App\Timesheet\Domain\Repository;

use Doctrine\Common\Collections\Collection; // ЗАПРЕЩЕНО в Domain-слое

interface WorkEntryRepositoryInterface
{
    public function findAll(): Collection; // используйте array
}

// -- Отсутствие маппинга: сохранение доменной сущности напрямую в Doctrine
final class WorkEntryRepository implements WorkEntryRepositoryInterface
{
    public function save(WorkEntry $workEntry): void
    {
        $this->em->persist($workEntry); // Domain Entity -- не Doctrine Entity!
        // Нужен маппинг через toOrmEntity()
    }
}

// -- Бизнес-логика в репозитории
final class WorkEntryRepository implements WorkEntryRepositoryInterface
{
    public function submitWorkEntry(WorkEntry $workEntry): void
    {
        $workEntry->submit(); // логика принадлежит домену, не репозиторию
        $this->save($workEntry);
    }
}

// -- Репозиторий для внутренней Entity агрегата (не Aggregate Root)
interface WorkEntryLineItemRepositoryInterface // WorkEntryLineItem -- часть агрегата WorkEntry
{
    // Репозитории создаются только для Aggregate Root.
    // Внутренние Entity сохраняются через репозиторий агрегата.
}
```
