---
name: validator
description: Правила создания кастомного Symfony валидатора — Constraint (атрибут) и ConstraintValidator (логика проверки) в Infrastructure-слое для валидации Request DTO
---

# Кастомный Symfony валидатор: правила и практики

## Ключевые принципы

- **Валидатор — инфраструктурный компонент**, расположенный в слое Infrastructure конкретного Bounded Context
- **Два класса в паре**: Constraint (атрибут, описывает правило) + ConstraintValidator (реализует логику проверки)
- **Используется для валидации Request DTO** — когда встроенных атрибутов `Symfony\Component\Validator\Constraints` недостаточно
- **Не содержит бизнес-логики домена** — только проверку формата, диапазонов, уникальности и межполевых зависимостей на уровне инфраструктуры
- **Сообщения об ошибках на русском языке** — согласно соглашениям проекта
- **Ошибки через `buildViolation()`** — валидатор не бросает исключений

---

## Архитектурное расположение

```
src/
├── {BoundedContext}/
│   └── Infrastructure/
│       ├── Validator/
│       │   ├── ValidWorkPeriod.php              # Constraint (атрибут)
│       │   └── ValidWorkPeriodValidator.php     # ConstraintValidator (логика)
│       └── Dto/
│           └── CreateWorkEntryRequestDto.php    # Request DTO использует Constraint
├── Shared/
│   └── Infrastructure/
│       └── Validator/
│           ├── UniqueEmail.php                  # общий Constraint
│           └── UniqueEmailValidator.php         # общий ConstraintValidator
```

**Почему так:**
- Валидатор зависит от Symfony Validator (`Constraint`, `ConstraintValidator`) — это инфраструктура
- Валидатор проверяет данные Request DTO — размещается рядом с DTO в `Infrastructure/`
- Если валидатор используется несколькими Bounded Context — размещается в `Shared/Infrastructure/Validator/`

---

## Правила именования

| Элемент             | Паттерн                   | Пример                        |
|---------------------|---------------------------|-------------------------------|
| Constraint          | `{RuleName}`              | `ValidWorkPeriod`             |
| ConstraintValidator | `{RuleName}Validator`     | `ValidWorkPeriodValidator`    |
| Файл Constraint     | `{RuleName}.php`          | `ValidWorkPeriod.php`         |
| Файл Validator      | `{RuleName}Validator.php` | `ValidWorkPeriodValidator.php`|

**Правила формулирования имён:**
- Имя Constraint отражает **проверяемое правило**, а не действие: `ValidWorkPeriod`, `UniqueEmail`, `ExistingEmployee`
- Не использовать глаголы в повелительном наклонении: `ValidWorkPeriod`, не `ValidateWorkPeriod`
- ConstraintValidator всегда именуется как Constraint + суффикс `Validator`

---

## 1. Constraint (атрибут)

Constraint — PHP-атрибут, описывающий правило валидации. Содержит сообщение об ошибке по умолчанию и дополнительные параметры.

### Шаблон Constraint

```php
// src/{BoundedContext}/Infrastructure/Validator/{RuleName}.php

declare(strict_types=1);

namespace App\{BoundedContext}\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

/** Правило валидации: {краткое описание правила}. */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
final class {RuleName} extends Constraint
{
    public string $message = 'Сообщение об ошибке по умолчанию на русском.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT; // или self::PROPERTY_CONSTRAINT
    }
}
```

### Когда использовать CLASS_CONSTRAINT vs PROPERTY_CONSTRAINT

| Тип                | Когда использовать                                               | Атрибут на        |
|--------------------|------------------------------------------------------------------|--------------------|
| `CLASS_CONSTRAINT` | Валидация зависит от нескольких полей DTO (межполевая проверка)  | Классе DTO         |
| `PROPERTY_CONSTRAINT` | Валидация одного поля (уникальность, существование, формат)  | Свойстве DTO       |

**Важно:** если Constraint может работать и на классе, и на свойстве, указывайте оба таргета в `#[\Attribute]`:
```php
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
```

Но метод `getTargets()` должен возвращать только один тип — тот, для которого написана логика в ConstraintValidator.

---

## 2. ConstraintValidator (логика)

ConstraintValidator — класс, реализующий логику проверки. Получает значение (объект или скаляр) и Constraint с параметрами.

### Шаблон ConstraintValidator

```php
// src/{BoundedContext}/Infrastructure/Validator/{RuleName}Validator.php

declare(strict_types=1);

namespace App\{BoundedContext}\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/** Проверяет {краткое описание проверяемого правила}. */
final class {RuleName}Validator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof {RuleName}) {
            throw new UnexpectedTypeException($constraint, {RuleName}::class);
        }

        // Для PROPERTY_CONSTRAINT: пропуск null/пустых значений
        // (валидация обязательности — ответственность #[Assert\NotBlank])
        if ($value === null || $value === '') {
            return;
        }

        // Логика валидации...

        // При нарушении:
        $this->context->buildViolation($constraint->message)
            ->addViolation();
    }
}
```

### Правила для ConstraintValidator

- **Первая строка `validate()`** — проверка типа Constraint через `UnexpectedTypeException`
- **Пропуск null/пустых значений** в PROPERTY_CONSTRAINT — валидация обязательности выполняется отдельно через `#[Assert\NotBlank]`
- **Ошибки через `buildViolation()`** — не бросать исключений (`\InvalidArgumentException`, `\DomainException` и т.д.)
- **Один валидатор — одно правило** — не совмещать несколько проверок в одном ConstraintValidator
- **Зависимости через конструктор** — если нужен репозиторий или сервис, внедряются через DI

### Вспомогательный метод getPropertyValue()

Для CLASS_CONSTRAINT, где нужно читать отдельные поля объекта без жёсткой привязки к конкретному DTO, используйте защищённый метод `getPropertyValue()`. Он пробует геттер (`get{Property}()`), затем публичное свойство.

Добавьте метод в ConstraintValidator:

```php
/**
 * Возвращает значение свойства объекта через геттер или публичное свойство.
 *
 * @throws \InvalidArgumentException если свойство недоступно
 */
private function getPropertyValue(object $object, string $property): mixed
{
    $getter   = 'get' . ucfirst($property);
    $isGetter = 'is' . ucfirst($property);

    if (method_exists($object, $getter)) {
        return $object->$getter();
    }

    if (method_exists($object, $isGetter)) {
        return $object->$isGetter();
    }

    if (property_exists($object, $property)) {
        return $object->$property;
    }

    throw new \InvalidArgumentException(sprintf(
        'Свойство "%s" не найдено в классе "%s": отсутствует геттер %s(), %s() и публичное свойство.',
        $property,
        $object::class,
        $getter,
        $isGetter,
    ));
}
```

**Когда использовать:**
- В CLASS_CONSTRAINT, когда валидатор должен работать с несколькими типами DTO (универсальный валидатор)
- В CLASS_CONSTRAINT, когда DTO может использовать как публичные свойства, так и геттеры

**Когда не использовать:**
- Если валидатор привязан к конкретному типу DTO (как `ValidWorkPeriodValidator`) и DTO — `final readonly class` с публичными свойствами — обращайтесь напрямую к полям после `instanceof`-проверки

---

## 3. Конкретные примеры

### Пример 1: валидация периода (CLASS_CONSTRAINT на DTO)

Проверяет, что `startDate <= endDate` в Request DTO. Требует доступа к нескольким полям — используется `CLASS_CONSTRAINT`.

**Constraint:**

```php
// src/Timesheet/Infrastructure/Validator/ValidWorkPeriod.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

/** Правило валидации: дата начала периода не позже даты окончания. */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ValidWorkPeriod extends Constraint
{
    public string $message = 'Дата начала периода не может быть позже даты окончания.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
```

**ConstraintValidator:**

```php
// src/Timesheet/Infrastructure/Validator/ValidWorkPeriodValidator.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Validator;

use App\Timesheet\Infrastructure\Dto\CreateWorkEntryRequestDto;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Проверяет, что дата начала рабочего периода не позже даты окончания. */
final class ValidWorkPeriodValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidWorkPeriod) {
            throw new UnexpectedTypeException($constraint, ValidWorkPeriod::class);
        }

        if (!$value instanceof CreateWorkEntryRequestDto) {
            throw new UnexpectedValueException($value, CreateWorkEntryRequestDto::class);
        }

        // Прямой доступ к публичным свойствам: DTO — final readonly class с известным типом
        $startDate = $this->getPropertyValue($value, 'startDate');
        $endDate   = $this->getPropertyValue($value, 'endDate');

        if ($startDate === '' || $endDate === '') {
            return;
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
        $end   = \DateTimeImmutable::createFromFormat('Y-m-d', $endDate);

        if ($start === false || $end === false) {
            return; // невалидный формат даты — обрабатывается #[Assert\Date]
        }

        if ($start > $end) {
            $this->context->buildViolation($constraint->message)
                ->atPath('endDate')
                ->addViolation();
        }
    }

    private function getPropertyValue(object $object, string $property): mixed
    {
        $getter   = 'get' . ucfirst($property);
        $isGetter = 'is' . ucfirst($property);

        if (method_exists($object, $getter)) {
            return $object->$getter();
        }

        if (method_exists($object, $isGetter)) {
            return $object->$isGetter();
        }

        if (property_exists($object, $property)) {
            return $object->$property;
        }

        throw new \InvalidArgumentException(sprintf(
            'Свойство "%s" не найдено в классе "%s": отсутствует геттер %s(), %s() и публичное свойство.',
            $property,
            $object::class,
            $getter,
            $isGetter,
        ));
    }
}
```

**Использование в Request DTO:**

```php
// src/Timesheet/Infrastructure/Dto/CreateWorkEntryRequestDto.php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use App\Timesheet\Infrastructure\Validator\ValidWorkPeriod;
use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание записи рабочего времени. */
#[ValidWorkPeriod]
final readonly class CreateWorkEntryRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Идентификатор сотрудника обязателен.')]
        #[Assert\Uuid(message: 'Идентификатор сотрудника должен быть валидным UUID.')]
        public string $employeeId,

        #[Assert\NotBlank(message: 'Дата начала обязательна.')]
        #[Assert\Date(message: 'Дата начала должна быть в формате YYYY-MM-DD.')]
        public string $startDate,

        #[Assert\NotBlank(message: 'Дата окончания обязательна.')]
        #[Assert\Date(message: 'Дата окончания должна быть в формате YYYY-MM-DD.')]
        public string $endDate,

        #[Assert\NotBlank(message: 'Количество часов обязательно.')]
        #[Assert\Positive(message: 'Количество часов должно быть положительным числом.')]
        public float $hours,
    ) {}
}
```

---

### Пример 2: валидация уникальности через репозиторий (PROPERTY_CONSTRAINT)

Проверяет, что email не занят другим пользователем. Validator внедряет репозиторий через конструктор.

**Constraint:**

```php
// src/Shared/Infrastructure/Validator/UniqueEmail.php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

/** Правило валидации: email-адрес не занят другим пользователем. */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class UniqueEmail extends Constraint
{
    public string $message = 'Пользователь с таким email уже существует.';

    public function __construct(
        public ?string $excludeId = null,
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
```

**ConstraintValidator:**

```php
// src/Shared/Infrastructure/Validator/UniqueEmailValidator.php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validator;

use App\Persistence\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Проверяет уникальность email-адреса среди существующих пользователей. */
final class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmail) {
            throw new UnexpectedTypeException($constraint, UniqueEmail::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $existingUser = $this->userRepository->findOneByEmail($value);

        if ($existingUser === null) {
            return;
        }

        // Исключаем текущего пользователя при обновлении
        if ($constraint->excludeId !== null && $existingUser->getId() === $constraint->excludeId) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->addViolation();
    }
}
```

**Использование в Request DTO:**

```php
// src/Employee/Infrastructure/Dto/CreateEmployeeRequestDto.php

declare(strict_types=1);

namespace App\Employee\Infrastructure\Dto;

use App\Shared\Infrastructure\Validator\UniqueEmail;
use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание сотрудника. */
final readonly class CreateEmployeeRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Имя сотрудника обязательно.')]
        #[Assert\Length(max: 255, maxMessage: 'Имя не должно превышать 255 символов.')]
        public string $name,

        #[Assert\NotBlank(message: 'Email обязателен.')]
        #[Assert\Email(message: 'Email должен быть валидным адресом электронной почты.')]
        #[UniqueEmail]
        public string $email,
    ) {}
}
```

---

## 4. Параметризация Constraint

Constraint может принимать дополнительные параметры для настройки поведения.

```php
// Constraint с параметрами
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class MaxDecimalPrecision extends Constraint
{
    public string $message = 'Значение не должно содержать более {{ precision }} знаков после запятой.';

    public function __construct(
        public int $precision = 2,
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
```

```php
// Использование с параметром
#[MaxDecimalPrecision(precision: 2)]
public float $hours,
```

**Правила параметризации:**
- Параметры Constraint — через promoted-свойства конструктора
- Обязательно передавать `$options`, `$groups`, `$payload` в `parent::__construct()`
- Плейсхолдеры в `message` оформляются в двойных фигурных скобках: `{{ precision }}`
- В ConstraintValidator подставлять параметры через `->setParameter()`:

```php
$this->context->buildViolation($constraint->message)
    ->setParameter('{{ precision }}', (string) $constraint->precision)
    ->addViolation();
```

---

## 5. Привязка пути ошибки (atPath)

Для `CLASS_CONSTRAINT` ошибка по умолчанию привязывается к классу целиком. Чтобы привязать к конкретному полю — используйте `->atPath()`.

```php
// Ошибка привязана к полю endDate
$this->context->buildViolation($constraint->message)
    ->atPath('endDate')
    ->addViolation();

// Ошибка привязана к классу (без atPath)
$this->context->buildViolation($constraint->message)
    ->addViolation();
```

**Рекомендация:** в `CLASS_CONSTRAINT` всегда привязывать ошибку к наиболее релевантному полю через `->atPath()` — это упрощает обработку ошибок на клиенте.

---

## 6. DI и автосвязывание

Symfony автоматически обнаруживает ConstraintValidator через `autoconfigure`:
- Symfony сканирует классы, наследующие `ConstraintValidator`, и регистрирует их как валидаторы
- Связь Constraint -> ConstraintValidator устанавливается по соглашению именования: `{RuleName}` -> `{RuleName}Validator`
- Дополнительная конфигурация в `services.php` **не требуется**

Если ConstraintValidator нуждается в зависимостях (репозиторий, сервис) — они внедряются через конструктор стандартным autowire:

```php
final class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}
    // ...
}
```

---

## 7. Unit-тесты

### Тест CLASS_CONSTRAINT (валидация периода)

```php
// tests/Unit/Timesheet/Infrastructure/Validator/ValidWorkPeriodValidatorTest.php

declare(strict_types=1);

namespace App\Tests\Unit\Timesheet\Infrastructure\Validator;

use App\Timesheet\Infrastructure\Dto\CreateWorkEntryRequestDto;
use App\Timesheet\Infrastructure\Validator\ValidWorkPeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** Проверяет кастомную валидацию рабочего периода на Request DTO. */
final class ValidWorkPeriodValidatorTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    #[Test]
    public function validPeriodPassesValidation(): void
    {
        $dto = new CreateWorkEntryRequestDto(
            employeeId: '550e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-01',
            endDate: '2026-03-15',
            hours: 8.0,
        );

        $errors = $this->validator->validate($dto);
        $periodErrors = $this->filterViolationsByConstraint($errors, ValidWorkPeriod::class);

        self::assertCount(0, $periodErrors);
    }

    #[Test]
    public function equalDatesPassValidation(): void
    {
        $dto = new CreateWorkEntryRequestDto(
            employeeId: '550e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-01',
            endDate: '2026-03-01',
            hours: 8.0,
        );

        $errors = $this->validator->validate($dto);
        $periodErrors = $this->filterViolationsByConstraint($errors, ValidWorkPeriod::class);

        self::assertCount(0, $periodErrors);
    }

    #[Test]
    public function startDateAfterEndDateFailsValidation(): void
    {
        $dto = new CreateWorkEntryRequestDto(
            employeeId: '550e8400-e29b-41d4-a716-446655440000',
            startDate: '2026-03-15',
            endDate: '2026-03-01',
            hours: 8.0,
        );

        $errors = $this->validator->validate($dto);
        $periodErrors = $this->filterViolationsByConstraint($errors, ValidWorkPeriod::class);

        self::assertCount(1, $periodErrors);
        self::assertSame('endDate', $periodErrors[0]->getPropertyPath());
    }

    /**
     * @return list<\Symfony\Component\Validator\ConstraintViolationInterface>
     */
    private function filterViolationsByConstraint(
        \Symfony\Component\Validator\ConstraintViolationListInterface $violations,
        string $constraintClass,
    ): array {
        $filtered = [];

        foreach ($violations as $violation) {
            if ($violation->getConstraint() instanceof $constraintClass) {
                $filtered[] = $violation;
            }
        }

        return $filtered;
    }
}
```

### Тест PROPERTY_CONSTRAINT (валидация уникальности)

```php
// tests/Unit/Shared/Infrastructure/Validator/UniqueEmailValidatorTest.php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Validator;

use App\Persistence\Entity\User;
use App\Persistence\Repository\UserRepository;
use App\Shared\Infrastructure\Validator\UniqueEmail;
use App\Shared\Infrastructure\Validator\UniqueEmailValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/** Проверяет валидацию уникальности email через кастомный валидатор. */
final class UniqueEmailValidatorTest extends TestCase
{
    private UserRepository $userRepository;
    private UniqueEmailValidator $validator;
    private ExecutionContextInterface $context;
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->validator = new UniqueEmailValidator($this->userRepository);

        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->violationBuilder->method('addViolation')->willReturn(null);

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    #[Test]
    public function uniqueEmailPassesValidation(): void
    {
        $this->userRepository
            ->method('findOneByEmail')
            ->willReturn(null);

        $this->context
            ->expects(self::never())
            ->method('buildViolation');

        $this->validator->validate('new@example.com', new UniqueEmail());
    }

    #[Test]
    public function duplicateEmailFailsValidation(): void
    {
        $existingUser = $this->createMock(User::class);
        $existingUser->method('getId')->willReturn('existing-id');

        $this->userRepository
            ->method('findOneByEmail')
            ->willReturn($existingUser);

        $this->context
            ->expects(self::once())
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate('taken@example.com', new UniqueEmail());
    }

    #[Test]
    public function excludedIdSkipsViolation(): void
    {
        $existingUser = $this->createMock(User::class);
        $existingUser->method('getId')->willReturn('current-user-id');

        $this->userRepository
            ->method('findOneByEmail')
            ->willReturn($existingUser);

        $this->context
            ->expects(self::never())
            ->method('buildViolation');

        $this->validator->validate(
            'taken@example.com',
            new UniqueEmail(excludeId: 'current-user-id'),
        );
    }

    #[Test]
    public function nullValueSkipsValidation(): void
    {
        $this->context
            ->expects(self::never())
            ->method('buildViolation');

        $this->validator->validate(null, new UniqueEmail());
    }

    #[Test]
    public function emptyStringSkipsValidation(): void
    {
        $this->context
            ->expects(self::never())
            ->method('buildViolation');

        $this->validator->validate('', new UniqueEmail());
    }
}
```

---

## Чек-лист при создании кастомного валидатора

### Constraint

- [ ] Constraint размещён в `src/{BoundedContext}/Infrastructure/Validator/`
- [ ] Класс объявлен как `final class`, наследует `Symfony\Component\Validator\Constraint`
- [ ] PHP-атрибут `#[\Attribute]` с корректными таргетами (`TARGET_CLASS`, `TARGET_PROPERTY` или оба)
- [ ] Метод `getTargets()` возвращает `self::CLASS_CONSTRAINT` или `self::PROPERTY_CONSTRAINT`
- [ ] Сообщение об ошибке (`$message`) на русском языке
- [ ] Параметры Constraint через promoted-свойства конструктора (если есть)
- [ ] Конструктор передаёт `$options`, `$groups`, `$payload` в `parent::__construct()` (если переопределён)
- [ ] PHPDoc-комментарий на классе описывает правило валидации

### ConstraintValidator

- [ ] ConstraintValidator размещён рядом с Constraint в `src/{BoundedContext}/Infrastructure/Validator/`
- [ ] Именование: `{RuleName}Validator` — строго соответствует имени Constraint
- [ ] Класс объявлен как `final class`, наследует `Symfony\Component\Validator\ConstraintValidator`
- [ ] Первая строка `validate()` — проверка типа через `UnexpectedTypeException`
- [ ] Для PROPERTY_CONSTRAINT: пропуск `null` и пустой строки (обязательность — через `#[Assert\NotBlank]`)
- [ ] Ошибки добавляются через `$this->context->buildViolation()->addViolation()`
- [ ] Для CLASS_CONSTRAINT: привязка ошибки к полю через `->atPath()`
- [ ] Для CLASS_CONSTRAINT с доступом к полям: использовать `getPropertyValue()` — сначала геттер, затем публичное свойство
- [ ] Зависимости (репозитории, сервисы) — через конструктор
- [ ] PHPDoc-комментарий на классе описывает проверяемое правило

### Тесты

- [ ] Unit-тест покрывает валидный случай (ошибок нет)
- [ ] Unit-тест покрывает невалидный случай (ошибка добавлена)
- [ ] Unit-тест проверяет пропуск null/пустых значений (для PROPERTY_CONSTRAINT)
- [ ] Unit-тест проверяет граничные случаи (равные даты, excludeId и т.д.)

---

## Антипаттерны

```php
// -- Бизнес-логика в валидаторе -- валидатор проверяет формат/существование, не бизнес-правила
final class ValidWorkPeriodValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        // Бизнес-правило: сотрудник не может работать более 12 часов в день
        if ($value->hours > 12) {
            $this->context->buildViolation('...')->addViolation();
        }
        // Это правило принадлежит домену (Domain Entity / Domain Service)
    }
}

// -- Бросание исключений вместо buildViolation
final class UniqueEmailValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->userRepository->findOneByEmail($value) !== null) {
            throw new \InvalidArgumentException('Email уже занят'); // НЕПРАВИЛЬНО
        }
        // Используйте $this->context->buildViolation($constraint->message)->addViolation()
    }
}

// -- Отсутствие проверки типа Constraint
final class ValidWorkPeriodValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        // Нет проверки: if (!$constraint instanceof ValidWorkPeriod)
        // Всегда проверяйте тип через UnexpectedTypeException
    }
}

// -- Валидация обязательности в кастомном валидаторе
final class UniqueEmailValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value === null) {
            $this->context->buildViolation('Email обязателен.')->addViolation(); // НЕПРАВИЛЬНО
            return;
        }
        // Обязательность — ответственность #[Assert\NotBlank], не кастомного валидатора
    }
}

// -- CLASS_CONSTRAINT без atPath
final class ValidWorkPeriodValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($start > $end) {
            $this->context->buildViolation($constraint->message)
                ->addViolation(); // ошибка привязана к классу — клиент не знает, какое поле исправить
        }
        // Используйте ->atPath('endDate') для привязки к конкретному полю
    }
}

// -- Валидатор в доменном слое
// src/Timesheet/Domain/Validator/ValidWorkPeriod.php — НЕПРАВИЛЬНО
// src/Timesheet/Infrastructure/Validator/ValidWorkPeriod.php — ПРАВИЛЬНО

// -- Несоответствие именования Constraint и Validator
// ValidWorkPeriod.php + WorkPeriodValidator.php — НЕПРАВИЛЬНО
// ValidWorkPeriod.php + ValidWorkPeriodValidator.php — ПРАВИЛЬНО
// Symfony связывает пару по соглашению: {ConstraintClass}Validator

// -- Обращение к БД в PROPERTY_CONSTRAINT на каждом поле массива
#[Assert\All([
    new UniqueEmail(), // N запросов к БД при N элементах в массиве
])]
public array $emails,
// Для массивов лучше использовать CLASS_CONSTRAINT с одним batch-запросом
```
