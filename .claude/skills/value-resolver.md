---
name: value-resolver
description: Правила создания Symfony Value Resolver для автоматического резолвинга аргументов контроллера из HTTP-запроса с десериализацией и валидацией DTO
---

# Symfony Value Resolver: правила и практики

## Ключевые принципы

- **Value Resolver** — инфраструктурный компонент, расположенный в слое Infrastructure конкретного Bounded Context
- **Наследуется** от одного из абстрактных резолверов из `App\Shared\Infrastructure\ValueResolver`
- **Привязка к DTO** через атрибут `#[AsTargetedValueResolver(SomeDtoClass::class)]` на классе resolver — указывает имя резолвера
- **Десериализация** данных HTTP-запроса в DTO через `Symfony\Component\Serializer\SerializerInterface` (реализована в базовом классе)
- **Валидация** DTO через `Symfony\Component\Validator\Validator\ValidatorInterface` — правила описаны в самом DTO через атрибуты (реализована в базовом классе)
- **При ошибках валидации** базовый класс выбрасывает `Symfony\Component\Validator\Exception\ValidationFailedException`
- **Автоматическая регистрация** — autowire + autoconfigure + атрибут `#[AsTargetedValueResolver]`

---

## Архитектурное расположение

Value Resolver является инфраструктурным компонентом, привязанным к конкретному Bounded Context.

```
src/
├── Shared/
│   └── Infrastructure/
│       └── ValueResolver/
│           ├── AbstractValueResolver.php       # базовый класс
│           └── AbstractJsonValueResolver.php   # для JSON-запросов
│           └── ....                            # другие специфичные базовые резолверы
├── {BoundedContext}/
│   └── Infrastructure/
│       ├── ValueResolver/
│       │   └── CreateWorkEntryValueResolver.php  # конкретный resolver
│       └── Dto/
│           └── CreateWorkEntryRequestDto.php     # DTO запроса
```

**Почему так:**
- Resolver зависит от Symfony (`ValueResolverInterface`, `SerializerInterface`) — это инфраструктура
- DTO запроса содержит атрибуты валидации Symfony — это инфраструктурный слой
- Абстрактные базовые классы в `Shared` доступны всем Bounded Contexts

---

## Абстрактные базовые классы (уже реализованы в Shared)

В проекте уже существуют два базовых класса:

**`AbstractValueResolver`** — базовый класс:
- Реализует `ValueResolverInterface`
- Принимает `SerializerInterface` и `ValidatorInterface` через конструктор
- Реализует метод `resolve()`: вызывает `supports()`, десериализует, валидирует
- Реализует метод `validate()`: при нарушениях выбрасывает `ValidationFailedException`
- Реализует метод `supports()`: проверяет тип аргумента через `getDtoRequestClass()`
- Абстрактный метод `getDtoRequestClass(): string` — возвращает FQCN DTO
- Абстрактный метод `deserialize(Request, ArgumentMetadata): object`

**`AbstractJsonValueResolver`** — для JSON-запросов (наследует AbstractValueResolver):
- Реализует метод `deserialize()` через `SerializerInterface::deserialize(..., 'json')`
- Метод `getContent()` выбрасывает `BadRequestHttpException` при пустом теле

**Выбор базового класса:**
- `AbstractJsonValueResolver` — для POST/PUT/PATCH с JSON body
- `AbstractValueResolver` — для GET-запросов или нестандартной десериализации (нужно реализовать `deserialize()` самостоятельно)

---

## 1. Value Resolver

```php
// src/{BoundedContext}/Infrastructure/ValueResolver/CreateWorkEntryValueResolver.php

declare(strict_types=1);

namespace App\{BoundedContext}\Infrastructure\ValueResolver;

use App\{BoundedContext}\Infrastructure\Dto\CreateWorkEntryRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/**
 * Десериализует и валидирует DTO запроса на создание записи рабочего времени из JSON body.
 */
#[AsTargetedValueResolver(CreateWorkEntryRequestDto::class)]
final class CreateWorkEntryValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return CreateWorkEntryRequestDto::class;
    }
}
```

**Что происходит при обработке запроса:**
1. Symfony видит атрибут `#[ValueResolver(...)]` на аргументе контроллера и выбирает `CreateWorkEntryValueResolver`
2. `resolve()` из базового класса вызывает `supports()` — проверяет тип аргумента
3. `deserialize()` из `AbstractJsonValueResolver` — читает JSON body, десериализует в DTO
4. `validate()` из `AbstractValueResolver` — валидирует DTO; при ошибках — `ValidationFailedException`
5. Готовый DTO передаётся в контроллер

---

## 2. DTO запроса с правилами валидации

DTO — readonly-класс с атрибутами валидации Symfony Validator. Не реализует никаких маркерных интерфейсов.

```php
// src/{BoundedContext}/Infrastructure/Dto/CreateWorkEntryRequestDto.php

declare(strict_types=1);

namespace App\{BoundedContext}\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание записи рабочего времени. */
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

## 3. Контроллер

Контроллер получает готовый валидированный DTO как аргумент метода. Атрибут `#[ValueResolver(...)]` связывает аргумент с конкретным resolver.

```php
// src/{BoundedContext}/Infrastructure/Controller/CreateWorkEntryController.php

declare(strict_types=1);

namespace App\{BoundedContext}\Infrastructure\Controller;

use App\{BoundedContext}\Infrastructure\Dto\CreateWorkEntryRequestDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер создания записи рабочего времени. */
final class CreateWorkEntryController
{
    #[Route('/api/work-entries', name: 'create_work_entry', methods: ['POST'])]
    public function __invoke(
        #[ValueResolver(CreateWorkEntryRequestDto::class)] CreateWorkEntryRequestDto $dto,
    ): JsonResponse {
        // DTO уже десериализован и валидирован через CreateWorkEntryValueResolver.
        // Здесь формируем Command и отправляем в шину команд.

        // $this->commandBus->dispatch(new RegisterWorkEntryCommand(
        //     employeeId: $dto->employeeId,
        //     startDate:  $dto->startDate,
        //     endDate:    $dto->endDate,
        //     hours:      $dto->hours,
        // ));

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
```

---

## 4. Регистрация сервисов

При стандартной конфигурации (autowire + autoconfigure включены, `App\\` сканируется из `src/`) **дополнительная регистрация не требуется**. Symfony автоматически:

- Обнаружит `CreateWorkEntryValueResolver` как `ValueResolverInterface` и зарегистрирует его
- Атрибут `#[AsTargetedValueResolver(CreateWorkEntryRequestDto::class)]` задаёт имя резолвера
- Атрибут `#[ValueResolver(CreateWorkEntryRequestDto::class)]` в контроллере выбирает нужный резолвер по имени

---

## 5. Unit-тест Value Resolver

```php
// tests/Unit/{BoundedContext}/Infrastructure/ValueResolver/CreateWorkEntryValueResolverTest.php

declare(strict_types=1);

namespace App\Tests\Unit\{BoundedContext}\Infrastructure\ValueResolver;

use App\{BoundedContext}\Infrastructure\Dto\CreateWorkEntryRequestDto;
use App\{BoundedContext}\Infrastructure\ValueResolver\CreateWorkEntryValueResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateWorkEntryValueResolverTest extends TestCase
{
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private CreateWorkEntryValueResolver $resolver;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator  = $this->createMock(ValidatorInterface::class);
        $this->resolver   = new CreateWorkEntryValueResolver($this->serializer, $this->validator);
    }

    #[Test]
    public function returnsEmptyForUnrelatedType(): void
    {
        $request  = new Request();
        $argument = new ArgumentMetadata('dto', \stdClass::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertSame([], $result);
    }

    #[Test]
    public function deserializesAndValidatesJsonBody(): void
    {
        $json    = '{"employeeId":"550e8400-e29b-41d4-a716-446655440000","startDate":"2024-01-01","endDate":"2024-01-31","hours":8.0}';
        $request = Request::create('/api/work-entries', 'POST', content: $json);
        $argument = new ArgumentMetadata('dto', CreateWorkEntryRequestDto::class, false, false, null);

        $dto = $this->createMock(CreateWorkEntryRequestDto::class);

        $this->serializer
            ->expects(self::once())
            ->method('deserialize')
            ->with($json, CreateWorkEntryRequestDto::class, 'json')
            ->willReturn($dto);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($dto)
            ->willReturn(new ConstraintViolationList());

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertCount(1, $result);
        self::assertSame($dto, $result[0]);
    }

    #[Test]
    public function throwsValidationFailedExceptionOnInvalidDto(): void
    {
        $json     = '{"employeeId":"invalid","hours":-1}';
        $request  = Request::create('/api/work-entries', 'POST', content: $json);
        $argument = new ArgumentMetadata('dto', CreateWorkEntryRequestDto::class, false, false, null);

        $dto = $this->createMock(CreateWorkEntryRequestDto::class);

        $this->serializer
            ->method('deserialize')
            ->willReturn($dto);

        $violation = new ConstraintViolation(
            message: 'Идентификатор сотрудника должен быть валидным UUID.',
            messageTemplate: '',
            parameters: [],
            root: $dto,
            propertyPath: 'employeeId',
            invalidValue: 'invalid',
        );

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList([$violation]));

        $this->expectException(ValidationFailedException::class);

        iterator_to_array($this->resolver->resolve($request, $argument));
    }
}
```

---

## Чек-лист при создании Value Resolver

- [ ] Resolver размещён в `src/{BoundedContext}/Infrastructure/ValueResolver/`
- [ ] Resolver наследует `AbstractJsonValueResolver` (JSON body) или `AbstractValueResolver` (иной источник данных)
- [ ] Resolver помечен атрибутом `#[AsTargetedValueResolver(SomeDtoClass::class)]`
- [ ] Resolver реализует метод `getDtoRequestClass(): string` — возвращает FQCN DTO
- [ ] При наследовании `AbstractValueResolver` реализован метод `deserialize()`
- [ ] DTO запроса — `final readonly class` с `Assert\*` атрибутами
- [ ] DTO размещён в `src/{BoundedContext}/Infrastructure/Dto/`
- [ ] Контроллер принимает DTO через аргумент с атрибутом `#[ValueResolver(SomeDtoClass::class)]`
- [ ] Дополнительная регистрация в `services.php` не требуется (autowire + autoconfigure)
- [ ] Unit-тесты покрывают: пропуск нерелевантных типов, десериализацию, выброс `ValidationFailedException`

---

## Антипаттерны

```php
// ❌ Десериализация и валидация прямо в контроллере — дублирование логики
final class CreateWorkEntryController
{
    public function __invoke(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize($request->getContent(), CreateWorkEntryRequestDto::class, 'json');
        $errors = $this->validator->validate($dto);
        // ... нарушает SRP, дублируется в каждом контроллере
    }
}

// ❌ Реализация ValueResolverInterface напрямую вместо наследования от абстрактного класса
final class CreateWorkEntryValueResolver implements ValueResolverInterface { ... }
// Используйте AbstractJsonValueResolver или AbstractValueResolver — они уже содержат
// логику supports(), validate() и resolve()

// ❌ Использование MapRequestPayload — встроенный resolver не позволяет контролировать
//    поведение десериализации и не вписывается в архитектуру проекта
public function __invoke(#[MapRequestPayload] CreateWorkEntryRequestDto $dto): JsonResponse
// Предпочитаем кастомный resolver

// ❌ DTO с публичными сеттерами — нарушает иммутабельность
class CreateWorkEntryRequestDto
{
    public string $employeeId;
    public function setEmployeeId(string $id): void { $this->employeeId = $id; }
}
// Используйте readonly class с конструктором

// ❌ Отсутствие атрибута #[ValueResolver(...)] в контроллере при использовании
//    нескольких resolver-ов для разных DTO — Symfony не сможет выбрать правильный
public function __invoke(CreateWorkEntryRequestDto $dto): JsonResponse { ... }
// Всегда указывайте #[ValueResolver(CreateWorkEntryRequestDto::class)]
```
