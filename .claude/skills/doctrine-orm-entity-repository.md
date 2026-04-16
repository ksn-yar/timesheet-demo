---
name: doctrine-orm-entity-repository
description: Правила создания Doctrine ORM Entity и Repository в домене Persistence — маппинг, генерация ID, миграции, тесты
---

# Doctrine Entity: правила и практики

## Ключевые принципы

- **Doctrine Entity — чистый ORM-класс** с примитивными типами (`string`, `int`, `float`, `\DateTimeImmutable`, `enum`). Без Value Objects, доменных событий и DDD-паттернов.
- **Маппинг через PHP Attributes** (не XML, не аннотации)
- **Генерация ID на стороне БД** — `#[ORM\GeneratedValue]` с IDENTITY или `gen_random_uuid()` в PostgreSQL. Не генерировать UUID в PHP-коде.
- **Публичные сеттеры разрешены** — Entity является простым data-классом ORM, сеттеры (`setStatus()`, `setHours()` и т.д.) допустимы
- **Конструктор необязателен** — Doctrine создаёт объекты через рефлексию. Если конструктор есть — без обязательных параметров
- **Все Entity и Repository живут в домене `Persistence`** — это разрешённый архитектурный компромисс проекта

---

## Архитектурное правило: домен Persistence

Все Doctrine Entity и реализации Doctrine Repository размещаются в едином домене `Persistence`. Остальные бизнес-домены (Timesheet, Payroll и т.д.) **не обращаются к Persistence напрямую** — только через ACL (Anti-Corruption Layer).

```
src/
├── Persistence/           <-- единый Doctrine-домен
│   ├── Entity/
│   │   ├── WorkEntry.php
│   │   └── Employee.php
│   └── Repository/
│       ├── WorkEntryRepository.php
│       └── EmployeeRepository.php
├── Timesheet/             <-- бизнес-домен, НЕ знает о Persistence напрямую
│   ├── Domain/
│   │   └── ...
│   └── Infrastructure/
│       └── Acl/           <-- ACL для доступа к Persistence
│           └── ...
└── Payroll/               <-- другой бизнес-домен
    └── ...
```

**Почему так:** единый Persistence-домен упрощает управление схемой БД, миграциями и Doctrine-конфигурацией. Бизнес-домены остаются чистыми от инфраструктурных деталей ORM.

---

## 1. Enum статуса

```php
// src/Persistence/Entity/Enum/WorkEntryStatus.php

enum WorkEntryStatus: string
{
    case Draft     = 'draft';
    case Submitted = 'submitted';
    case Approved  = 'approved';
}
```

---

## 2. Doctrine Entity

```php
// src/Persistence/Entity/WorkEntry.php

use Doctrine\ORM\Mapping as ORM;
use App\Persistence\Repository\WorkEntryRepository;

/** Doctrine-сущность для записи рабочего времени. Чистый ORM-класс без бизнес-логики. */
#[ORM\Entity(repositoryClass: WorkEntryRepository::class)]
#[ORM\Table(name: 'work_entries')]
#[ORM\Index(columns: ['employee_id'], name: 'idx_work_entries_employee_id')]
class WorkEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $id = null;

    #[ORM\Column(type: 'guid')]
    private string $employeeId;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $endDate;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $hours;

    #[ORM\Column(type: 'string', enumType: WorkEntryStatus::class, length: 20)]
    private WorkEntryStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    public function setEmployeeId(string $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getHours(): string
    {
        return $this->hours;
    }

    public function setHours(string $hours): void
    {
        $this->hours = $hours;
    }

    public function getStatus(): WorkEntryStatus
    {
        return $this->status;
    }

    public function setStatus(WorkEntryStatus $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
```

### Альтернативный вариант: UUID через `gen_random_uuid()` (PostgreSQL)

Вместо `doctrine.uuid_generator` можно использовать генерацию UUID на уровне PostgreSQL:

```php
#[ORM\Id]
#[ORM\Column(type: 'guid', unique: true, options: ['default' => 'gen_random_uuid()'])]
private ?string $id = null;
```

### Альтернативный вариант: целочисленный ID

Если требуется автоинкрементный integer ID (не рекомендуется при наличии доменных Value Object типа UUID):

```php
#[ORM\Id]
#[ORM\GeneratedValue(strategy: 'IDENTITY')]
#[ORM\Column(type: 'integer')]
private ?int $id = null;

public function getId(): ?int { return $this->id; }
```

**Важно:** при использовании целочисленного ID и доменных Value Objects типа `WorkEntryId` (валидирующих UUID) маппинг в `toDomainEntity()` потребует отдельного строкового представления ID.

---

## 3. Association Mapping (связи между сущностями)

Если между Entity существуют связи (например, `WorkEntry` принадлежит `Employee`), они **должны быть объявлены через Association Mapping атрибуты** Doctrine, а не через простое хранение `employeeId` как `string`.

### Типы связей

| Атрибут | Направление | Описание |
|---|---|---|
| `#[ORM\ManyToOne]` | Many→One | Много записей ссылаются на одну сущность |
| `#[ORM\OneToMany]` | One→Many | Одна сущность содержит коллекцию дочерних |
| `#[ORM\ManyToMany]` | Many→Many | Обе стороны содержат коллекции |
| `#[ORM\OneToOne]` | One→One | Одна к одной |

### Пример: ManyToOne (WorkEntry → Employee)

```php
use Doctrine\ORM\Mapping as ORM;
use App\Persistence\Entity\Employee;

#[ORM\Entity(repositoryClass: WorkEntryRepository::class)]
#[ORM\Table(name: 'work_entries')]
class WorkEntry
{
    // ...

    /** Связь с сотрудником — владелец ассоциации (хранит FK в своей таблице) */
    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'id', nullable: false)]
    private Employee $employee;

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }
}
```

### Пример: OneToMany (Employee → WorkEntry[])

```php
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'employees')]
class Employee
{
    // ...

    /** Коллекция записей рабочего времени. Обратная сторона ассоциации (mappedBy). */
    #[ORM\OneToMany(targetEntity: WorkEntry::class, mappedBy: 'employee', cascade: ['persist', 'remove'])]
    private Collection $workEntries;

    public function __construct()
    {
        $this->workEntries = new ArrayCollection();
    }

    /** @return Collection<int, WorkEntry> */
    public function getWorkEntries(): Collection
    {
        return $this->workEntries;
    }
}
```

### Правила Association Mapping

- **Владелец ассоциации** (owning side) — сторона, которая хранит FK-колонку в таблице. Обычно это `ManyToOne` или сторона без `mappedBy`.
- **Обратная сторона** (inverse side) — объявляет `mappedBy`, ссылаясь на поле владельца. Doctrine управляет связью только через владельца.
- **`cascade`** — используйте осторожно. `cascade: ['persist']` разрешает каскадное сохранение, `cascade: ['remove']` — каскадное удаление. Не добавляйте без явной необходимости.
- **Lazy Loading по умолчанию** — связанные сущности загружаются лениво (`fetch: LAZY`). Для явной загрузки используйте `fetch: EAGER` или `JOIN` в запросе репозитория.
- **Индексы** — если FK-колонка участвует в частых запросах, добавьте `#[ORM\Index]` на классе Entity.
- **Nullable FK** — если связь необязательна, указывайте `nullable: true` в `#[ORM\JoinColumn]` и тип поля как `?Employee`.

### Антипаттерны Association Mapping

```php
// -- Хранить только ID вместо ассоциации, если нужна навигация по объектам
#[ORM\Column(type: 'guid')]
private string $employeeId;  // ОК только если Employee никогда не нужен через эту Entity

// -- Добавлять cascade: ['remove'] без понимания последствий
// Удаление Employee автоматически удалит все его WorkEntry
#[ORM\OneToMany(targetEntity: WorkEntry::class, cascade: ['remove'])]

// -- Двунаправленная связь без mappedBy/inversedBy — Doctrine не знает об обратной стороне
#[ORM\ManyToOne(targetEntity: Employee::class)]
// и одновременно в Employee без mappedBy на поле WorkEntry

// -- Инициализировать Collection через массив, а не ArrayCollection
private array $workEntries = [];  // используйте ArrayCollection
```

---

## 4. Doctrine Repository

Реализация репозитория наследуется от `ServiceEntityRepository`:

```php
// src/Persistence/Repository/WorkEntryRepository.php

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Persistence\Entity\WorkEntry;

/**
 * Репозиторий для работы с записями рабочего времени через Doctrine ORM.
 * 
 * @extends ServiceEntityRepository<WorkEntry>
 *
 * @method null|WorkEntry find($id, $lockMode = null, $lockVersion = null)
 * @method null|WorkEntry findOneBy(array $criteria, ?array $orderBy = null)
 * @method WorkEntry[]    findAll()
 * @method WorkEntry[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class WorkEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkEntry::class);
    }

    public function save(WorkEntry $entry, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entry);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WorkEntry $entry, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entry);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return WorkEntry[] */
    public function findByEmployee(string $employeeId): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.employeeId = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('w.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
```

---

## 5. Миграции

Миграции генерируются автоматически на основе разницы между текущей схемой БД и маппингом Entity. Не нужно писать миграции вручную.

```bash
# Сгенерировать миграцию на основе diff между Entity и текущей схемой БД
php bin/console doctrine:migrations:diff

# Применить миграции
php bin/console doctrine:migrations:migrate
```

**Важно:** после создания Entity или изменения атрибутов маппинга всегда запускайте `doctrine:migrations:diff` и проверяйте сгенерированный SQL перед применением.

---

## 6. Интеграционный тест репозитория

```php
// tests/Integration/Persistence/Repository/WorkEntryRepositoryTest.php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Persistence\Entity\WorkEntry;
use App\Persistence\Entity\Enum\WorkEntryStatus;
use App\Persistence\Repository\WorkEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

/** Интеграционные тесты для WorkEntryRepository. Проверяют реальное взаимодействие с БД. */
final class WorkEntryRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private WorkEntryRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em         = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(WorkEntryRepository::class);
    }

    public function test_saves_and_finds_work_entry(): void
    {
        $entry = new WorkEntry();
        $entry->setEmployeeId('550e8400-e29b-41d4-a716-446655440000');
        $entry->setStartDate(new \DateTimeImmutable('2026-03-01'));
        $entry->setEndDate(new \DateTimeImmutable('2026-03-15'));
        $entry->setHours('80.00');
        $entry->setStatus(WorkEntryStatus::Draft);
        $entry->setCreatedAt(new \DateTimeImmutable());

        $this->repository->save($entry, flush: true);
        $this->em->clear();

        $found = $this->repository->find($entry->getId());

        self::assertNotNull($found);
        self::assertSame($entry->getId(), $found->getId());
        self::assertSame('80.00', $found->getHours());
        self::assertSame(WorkEntryStatus::Draft, $found->getStatus());
    }

    public function test_find_by_employee_returns_entries_ordered_by_date(): void
    {
        $employeeId = '550e8400-e29b-41d4-a716-446655440000';

        $entry1 = new WorkEntry();
        $entry1->setEmployeeId($employeeId);
        $entry1->setStartDate(new \DateTimeImmutable('2026-03-01'));
        $entry1->setEndDate(new \DateTimeImmutable('2026-03-15'));
        $entry1->setHours('40.00');
        $entry1->setStatus(WorkEntryStatus::Draft);
        $entry1->setCreatedAt(new \DateTimeImmutable());

        $entry2 = new WorkEntry();
        $entry2->setEmployeeId($employeeId);
        $entry2->setStartDate(new \DateTimeImmutable('2026-03-16'));
        $entry2->setEndDate(new \DateTimeImmutable('2026-03-31'));
        $entry2->setHours('80.00');
        $entry2->setStatus(WorkEntryStatus::Draft);
        $entry2->setCreatedAt(new \DateTimeImmutable());

        $this->repository->save($entry1);
        $this->repository->save($entry2, flush: true);
        $this->em->clear();

        $entries = $this->repository->findByEmployee($employeeId);

        self::assertCount(2, $entries);
        // Сортировка DESC — сначала более поздняя запись
        self::assertSame('80.00', $entries[0]->getHours());
    }

    public function test_find_returns_null_for_unknown_id(): void
    {
        $result = $this->repository->find('00000000-0000-0000-0000-000000000000');

        self::assertNull($result);
    }
}
```

---

## Чек-лист при создании новой Entity

- [ ] Entity размещена в `src/Persistence/Entity/`
- [ ] Repository размещён в `src/Persistence/Repository/` и наследуется от `ServiceEntityRepository`
- [ ] ID генерируется на стороне БД (`#[ORM\GeneratedValue]`), а не в PHP-коде
- [ ] Поля используют примитивные типы (`string`, `int`, `float`, `\DateTimeImmutable`)
- [ ] Enum маппится через `enumType:` в атрибуте колонки
- [ ] Конструктор отсутствует или без обязательных параметров (Doctrine создаёт объекты через рефлексию)
- [ ] Публичные сеттеры для изменяемых полей
- [ ] Связи между Entity объявлены через Association Mapping (`#[ORM\ManyToOne]`, `#[ORM\OneToMany]`, `#[ORM\ManyToMany]`, `#[ORM\OneToOne]`), если таковые есть
- [ ] Владелец ассоциации (owning side) хранит FK-колонку и не имеет `mappedBy`
- [ ] Обратная сторона (inverse side) объявляет `mappedBy`, указывая на поле владельца
- [ ] Коллекции инициализируются через `ArrayCollection` в конструкторе
- [ ] `cascade` указан только при явной необходимости каскадного поведения
- [ ] Индексы объявлены через `#[ORM\Index]` на классе
- [ ] Миграция сгенерирована через `doctrine:migrations:diff`
- [ ] Интеграционный тест вызывает `$em->clear()` перед повторным чтением из БД
- [ ] Бизнес-домены обращаются к Entity/Repository только через ACL

---

## Антипаттерны

```php
// -- Публичные поля (не путать с сеттерами) -- нарушают инкапсуляцию
#[ORM\Column]
public string $status;  // используйте private + геттер/сеттер

// -- Генерация UUID в PHP-коде -- в этом проекте ID генерируется БД
$this->id = Uuid::v7()->toRfc4122();

// -- Value Objects в Entity -- Doctrine Entity работает с примитивами
#[ORM\Column(type: 'work_entry_id')]
private WorkEntryId $id;  // используйте string или int

// -- Доменные события в Entity -- это ответственность доменной модели, не ORM-класса
use RecordsDomainEvents;
$this->record(new WorkEntryCreated(...));

// -- Repository не наследует ServiceEntityRepository
final class WorkEntryRepository implements WorkEntryRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em) {}
    // используйте ServiceEntityRepository
}

// -- Entity размещена в бизнес-домене -- все Entity живут в Persistence
// src/Timesheet/Entity/WorkEntry.php       <-- неправильно
// src/Persistence/Entity/WorkEntry.php     <-- правильно

// -- Бизнес-логика в Entity через lifecycle callback
#[ORM\PrePersist]
public function validate(): void
{
    if ($this->hours <= 0) { ... }  // логика должна быть в доменном слое
}

// -- Ручное написание миграций вместо автогенерации
// Используйте: php bin/console doctrine:migrations:diff
```
