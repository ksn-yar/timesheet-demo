---
name: enum
description: Правила создания PHP enum — доменное перечисление с кейсами в PascalCase, backed-типом string и методом getLabel() для русскоязычных меток
---

# PHP Enum: правила и практики

## Ключевые принципы

- **Enum — доменный компонент**, расположенный в слое Domain конкретного Bounded Context
- **Backed enum со `string`-значением** — значения кейсов в `snake_case` для хранения в БД и сериализации
- **Кейсы именуются в `PascalCase`** — соответствует стандартам PHP
- **Метод `getLabel(): string`** — возвращает человекочитаемую метку на русском языке
- **Содержит только ограниченную бизнес-логику**: предикаты (`isEditable()`, `isFinal()`) и логику переходов (`canTransitionTo()`, `allowedTransitions()`) — не мутации внешних объектов
- **Используется доменными сущностями и Value Objects** — не зависит от инфраструктуры
- **Исключение — Persistence-домен**: если enum нужен только для Doctrine Entity маппинга (`enumType:`), допустимо создать его в `Persistence/Entity/Enum/` как архитектурный компромисс (не дублировать доменный enum)

---

## Архитектурное расположение

```
src/
├── {BoundedContext}/
│   └── Domain/
│       ├── Enum/
│       │   ├── WorkEntryStatus.php     # статус записи рабочего времени
│       │   ├── EmployeeRole.php        # роль сотрудника
│       │   └── ApprovalDecision.php    # решение по согласованию
│       ├── Entity/
│       │   └── WorkEntry.php           # Domain Entity использует Enum
│       └── ValueObject/
│           └── ...
├── Shared/
│   └── Domain/
│       └── Enum/
│           └── Currency.php            # общий Enum для нескольких Bounded Context
```

**Почему так:**
- Enum описывает допустимые значения доменного понятия — это часть Ubiquitous Language
- Enum не зависит от инфраструктуры (`Symfony`, `Doctrine`) — размещается в `Domain/Enum/`
- Если enum используется несколькими Bounded Context — размещается в `Shared/Domain/Enum/`

**Исключение: Persistence-домен**

Если enum нужен исключительно для `enumType:` в Doctrine Entity и не несёт доменного смысла — допустимо создать его в `Persistence/Entity/Enum/`. Это архитектурный компромисс: один enum на два контекста (Domain + Persistence) не всегда оправдан, если модели расходятся.

```
src/
├── Persistence/
│   └── Entity/
│       └── Enum/
│           └── WorkEntryStatus.php   # только для ORM-маппинга
```

**Важно:** если enum находится в `Domain/Enum/` — именно его указывайте в `enumType:` у Doctrine Entity. Не создавайте дублирующий enum в Persistence без необходимости.

---

## Правила именования

| Элемент        | Паттерн               | Пример              |
|----------------|------------------------|----------------------|
| Enum-класс     | `{DomainConcept}`      | `WorkEntryStatus`    |
| Кейс           | `PascalCase`           | `Draft`, `Approved`  |
| Backed-значение| `snake_case`           | `'draft'`, `'in_review'` |
| Файл           | `{DomainConcept}.php`  | `WorkEntryStatus.php`|

**Правила формулирования имён:**
- Имя enum отражает **доменное понятие**: `WorkEntryStatus`, `EmployeeRole`, `PaymentType`
- Не использовать суффикс `Enum` в имени класса: `WorkEntryStatus`, не `WorkEntryStatusEnum`
- Кейсы именуют **состояния или значения**, не действия: `Approved`, не `Approve`

---

## 1. Шаблон Enum

```php
// src/{BoundedContext}/Domain/Enum/{DomainConcept}.php

declare(strict_types=1);

namespace App\{BoundedContext}\Domain\Enum;

/** Перечисление {описание доменного понятия}. */
enum {DomainConcept}: string
{
    case First = 'first';
    case Second = 'second';
    case Third = 'third';

    /** Возвращает человекочитаемую метку на русском языке. */
    public function getLabel(): string
    {
        return match ($this) {
            self::First => 'Первый',
            self::Second => 'Второй',
            self::Third => 'Третий',
        };
    }
}
```

---

## 2. Конкретные примеры

### Пример 1: статус записи рабочего времени

```php
// src/Timesheet/Domain/Enum/WorkEntryStatus.php

declare(strict_types=1);

namespace App\Timesheet\Domain\Enum;

/** Перечисление статусов записи рабочего времени. */
enum WorkEntryStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /** Возвращает человекочитаемую метку на русском языке. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Submitted => 'Отправлена',
            self::InReview => 'На рассмотрении',
            self::Approved => 'Утверждена',
            self::Rejected => 'Отклонена',
        };
    }
}
```

### Пример 2: роль сотрудника

```php
// src/Employee/Domain/Enum/EmployeeRole.php

declare(strict_types=1);

namespace App\Employee\Domain\Enum;

/** Перечисление ролей сотрудников в системе. */
enum EmployeeRole: string
{
    case Employee = 'employee';
    case Manager = 'manager';
    case Admin = 'admin';

    /** Возвращает человекочитаемую метку на русском языке. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Employee => 'Сотрудник',
            self::Manager => 'Руководитель',
            self::Admin => 'Администратор',
        };
    }
}
```

### Пример 3: enum с дополнительными методами (при необходимости)

```php
// src/Timesheet/Domain/Enum/WorkEntryStatus.php

declare(strict_types=1);

namespace App\Timesheet\Domain\Enum;

/** Перечисление статусов записи рабочего времени. */
enum WorkEntryStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /** Возвращает человекочитаемую метку на русском языке. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Submitted => 'Отправлена',
            self::InReview => 'На рассмотрении',
            self::Approved => 'Утверждена',
            self::Rejected => 'Отклонена',
        };
    }

    /** Проверяет, допускает ли текущий статус редактирование записи. */
    public function isEditable(): bool
    {
        return match ($this) {
            self::Draft, self::Rejected => true,
            self::Submitted, self::InReview, self::Approved => false,
        };
    }

    /** Возвращает список статусов, в которые допустим переход из текущего. */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::InReview, self::Rejected],
            self::InReview => [self::Approved, self::Rejected],
            self::Approved => [],
            self::Rejected => [self::Draft],
        };
    }

    /** Проверяет, допустим ли переход в указанный статус. */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
```

---

## 3. Использование в Domain Entity

```php
// src/Timesheet/Domain/Entity/WorkEntry.php

declare(strict_types=1);

namespace App\Timesheet\Domain\Entity;

use App\Timesheet\Domain\Enum\WorkEntryStatus;

final class WorkEntry
{
    private WorkEntryStatus $status;

    public static function create(/* ... */): self
    {
        $entry = new self();
        $entry->status = WorkEntryStatus::Draft;
        // ...

        return $entry;
    }

    public function submit(): void
    {
        if (!$this->status->canTransitionTo(WorkEntryStatus::Submitted)) {
            throw new \DomainException(sprintf(
                'Невозможно отправить запись в статусе "%s".',
                $this->status->getLabel(),
            ));
        }

        $this->status = WorkEntryStatus::Submitted;
    }
}
```

---

## 4. Использование в Doctrine Entity (Persistence)

Для хранения enum в базе данных Doctrine Entity использует backed-значение (`string`).

```php
// src/Persistence/Entity/WorkEntry.php

use App\Timesheet\Domain\Enum\WorkEntryStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class WorkEntry
{
    #[ORM\Column(type: 'string', length: 20, enumType: WorkEntryStatus::class)]
    private WorkEntryStatus $status;

    public function getStatus(): WorkEntryStatus
    {
        return $this->status;
    }

    public function setStatus(WorkEntryStatus $status): void
    {
        $this->status = $status;
    }
}
```

**Важно:** Doctrine 2.11+ поддерживает параметр `enumType` в `#[ORM\Column]` — маппинг backed enum на строковый столбец выполняется автоматически.

---

## 5. Использование в Request DTO (валидация)

В Request DTO enum передаётся как строка. Для валидации используется `#[Assert\Choice]` с backed-значениями enum.

```php
// src/Timesheet/Infrastructure/Dto/CreateWorkEntryRequestDto.php

use App\Timesheet\Domain\Enum\WorkEntryStatus;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateWorkEntryRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Статус обязателен.')]
        #[Assert\Choice(
            callback: [WorkEntryStatus::class, 'values'],
            message: 'Недопустимый статус. Допустимые значения: {{ choices }}.',
        )]
        public string $status,
    ) {}
}
```

Если используется метод `values()`, добавьте его в enum:

```php
/** Возвращает массив всех backed-значений для валидации. */
public static function values(): array
{
    return array_column(self::cases(), 'value');
}
```

---

## 6. Использование в Response DTO / Presenter

В Response DTO и Presenter enum передаётся как строка (backed-значение) или как пара `value` + `label`.

```php
// В Presenter — передача значения и метки
$this->response = new JsonResponse([
    'status' => $dto->status,        // backed-значение: 'draft'
    'statusLabel' => WorkEntryStatus::from($dto->status)->getLabel(), // 'Черновик'
]);
```

---

## 7. Unit-тесты Enum

```php
// tests/Unit/{BoundedContext}/Domain/Enum/{DomainConcept}Test.php

declare(strict_types=1);

namespace App\Tests\Unit\{BoundedContext}\Domain\Enum;

use App\{BoundedContext}\Domain\Enum\{DomainConcept};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет кейсы, метки и backed-значения enum {DomainConcept}. */
final class {DomainConcept}Test extends TestCase
{
    #[Test]
    #[DataProvider('labelProvider')]
    public function returnsCorrectLabel({DomainConcept} $case, string $expectedLabel): void
    {
        self::assertSame($expectedLabel, $case->getLabel());
    }

    public static function labelProvider(): iterable
    {
        yield 'First' => [{DomainConcept}::First, 'Первый'];
        yield 'Second' => [{DomainConcept}::Second, 'Второй'];
        yield 'Third' => [{DomainConcept}::Third, 'Третий'];
    }

    #[Test]
    #[DataProvider('backedValueProvider')]
    public function createsFromBackedValue(string $value, {DomainConcept} $expectedCase): void
    {
        self::assertSame($expectedCase, {DomainConcept}::from($value));
    }

    public static function backedValueProvider(): iterable
    {
        yield 'first' => ['first', {DomainConcept}::First];
        yield 'second' => ['second', {DomainConcept}::Second];
        yield 'third' => ['third', {DomainConcept}::Third];
    }

    #[Test]
    public function tryFromReturnsNullForInvalidValue(): void
    {
        self::assertNull({DomainConcept}::tryFrom('nonexistent'));
    }

    #[Test]
    public function allCasesHaveLabels(): void
    {
        foreach ({DomainConcept}::cases() as $case) {
            self::assertNotEmpty($case->getLabel(), sprintf(
                'Кейс %s::%s не имеет метки.',
                {DomainConcept}::class,
                $case->name,
            ));
        }
    }
}
```

### Конкретный пример

```php
// tests/Unit/Timesheet/Domain/Enum/WorkEntryStatusTest.php

declare(strict_types=1);

namespace App\Tests\Unit\Timesheet\Domain\Enum;

use App\Timesheet\Domain\Enum\WorkEntryStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Проверяет кейсы, метки и backed-значения enum WorkEntryStatus. */
final class WorkEntryStatusTest extends TestCase
{
    #[Test]
    #[DataProvider('labelProvider')]
    public function returnsCorrectLabel(WorkEntryStatus $case, string $expectedLabel): void
    {
        self::assertSame($expectedLabel, $case->getLabel());
    }

    public static function labelProvider(): iterable
    {
        yield 'Draft' => [WorkEntryStatus::Draft, 'Черновик'];
        yield 'Submitted' => [WorkEntryStatus::Submitted, 'Отправлена'];
        yield 'InReview' => [WorkEntryStatus::InReview, 'На рассмотрении'];
        yield 'Approved' => [WorkEntryStatus::Approved, 'Утверждена'];
        yield 'Rejected' => [WorkEntryStatus::Rejected, 'Отклонена'];
    }

    #[Test]
    #[DataProvider('backedValueProvider')]
    public function createsFromBackedValue(string $value, WorkEntryStatus $expectedCase): void
    {
        self::assertSame($expectedCase, WorkEntryStatus::from($value));
    }

    public static function backedValueProvider(): iterable
    {
        yield 'draft' => ['draft', WorkEntryStatus::Draft];
        yield 'submitted' => ['submitted', WorkEntryStatus::Submitted];
        yield 'in_review' => ['in_review', WorkEntryStatus::InReview];
        yield 'approved' => ['approved', WorkEntryStatus::Approved];
        yield 'rejected' => ['rejected', WorkEntryStatus::Rejected];
    }

    #[Test]
    public function tryFromReturnsNullForInvalidValue(): void
    {
        self::assertNull(WorkEntryStatus::tryFrom('nonexistent'));
    }

    #[Test]
    public function allCasesHaveLabels(): void
    {
        foreach (WorkEntryStatus::cases() as $case) {
            self::assertNotEmpty($case->getLabel(), sprintf(
                'Кейс %s::%s не имеет метки.',
                WorkEntryStatus::class,
                $case->name,
            ));
        }
    }
}
```

---

## Чек-лист при создании Enum

### Enum (Domain)

- [ ] Enum размещён в `src/{BoundedContext}/Domain/Enum/`
- [ ] Backed enum: `enum {Name}: string`
- [ ] Кейсы именованы в `PascalCase`
- [ ] Backed-значения в `snake_case`
- [ ] Метод `getLabel(): string` возвращает метки на русском языке
- [ ] `match` в `getLabel()` покрывает все кейсы (без `default`)
- [ ] Нет зависимостей от инфраструктуры (`Symfony`, `Doctrine`)
- [ ] Нет суффикса `Enum` в имени класса
- [ ] PHPDoc-комментарий на enum описывает доменное понятие

### Дополнительные методы (при необходимости)

- [ ] Предикаты (`isEditable()`, `isFinal()`) — возвращают `bool` через `match` без `default`
- [ ] Переходы (`allowedTransitions()`, `canTransitionTo()`) — если enum описывает состояния с переходами
- [ ] `values(): array` — если нужен массив backed-значений для валидации в Request DTO

### Использование

- [ ] Domain Entity хранит enum как типизированное свойство (не строку)
- [ ] Doctrine Entity использует `enumType` в `#[ORM\Column]` для автоматического маппинга
- [ ] Request DTO валидирует строковое значение через `#[Assert\Choice]`
- [ ] Response DTO / Presenter передаёт backed-значение и при необходимости метку

### Тесты

- [ ] Unit-тест проверяет `getLabel()` для каждого кейса через `DataProvider`
- [ ] Unit-тест проверяет создание из backed-значения через `from()`
- [ ] Unit-тест проверяет `tryFrom()` для невалидного значения
- [ ] Unit-тест проверяет, что все кейсы имеют непустую метку

---

## Антипаттерны

```php
// -- Суффикс Enum в имени -- избыточен
enum WorkEntryStatusEnum: string { /* ... */ }
// Используйте: enum WorkEntryStatus: string

// -- Кейсы не в PascalCase
enum WorkEntryStatus: string
{
    case DRAFT = 'draft';        // UPPER_CASE — неправильно
    case in_review = 'in_review'; // snake_case — неправильно
}
// Используйте PascalCase: case Draft, case InReview

// -- Backed-значения в PascalCase или camelCase
enum WorkEntryStatus: string
{
    case Draft = 'Draft';       // PascalCase — неправильно
    case InReview = 'inReview'; // camelCase — неправильно
}
// Backed-значения в snake_case: 'draft', 'in_review'

// -- Отсутствие getLabel() — нет русскоязычных меток
enum WorkEntryStatus: string
{
    case Draft = 'draft';
    // Нет getLabel() — невозможно получить метку для UI
}

// -- default в match — скрывает ошибки при добавлении нового кейса
public function getLabel(): string
{
    return match ($this) {
        self::Draft => 'Черновик',
        default => 'Неизвестно', // при добавлении нового кейса ошибка не обнаружится
    };
}
// Перечисляйте все кейсы явно — PHP выбросит ошибку при добавлении нового кейса без метки

// -- Мутации внешних объектов в enum — это запрещено
enum WorkEntryStatus: string
{
    public function approve(WorkEntry $entry): void
    {
        $entry->setStatus(self::Approved); // ЗАПРЕЩЕНО: мутация внешнего объекта — это ответственность Domain Entity
    }
}
// Допустимы: предикаты (isEditable), переходы (canTransitionTo, allowedTransitions)
// Запрещены: мутации внешних объектов, обращения к репозиториям, инфраструктурные зависимости

// -- Enum в Infrastructure слое
// src/Timesheet/Infrastructure/Enum/WorkEntryStatus.php — НЕПРАВИЛЬНО
// src/Timesheet/Domain/Enum/WorkEntryStatus.php — ПРАВИЛЬНО

// -- Unbacked enum для значений, хранимых в БД
enum WorkEntryStatus // без `: string` — нельзя сохранить в БД через Doctrine
{
    case Draft;
}
// Используйте backed enum: enum WorkEntryStatus: string

// -- Инфраструктурные зависимости в enum
namespace App\Timesheet\Domain\Enum;

use Doctrine\ORM\Mapping as ORM;  // ЗАПРЕЩЕНО в Domain-слое
use Symfony\Component\Validator\Constraints as Assert; // ЗАПРЕЩЕНО в Domain-слое

// Enum — чистый доменный компонент без зависимостей от инфраструктуры
```
