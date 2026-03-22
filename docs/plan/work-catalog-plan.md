# План реализации домена Work Catalog

**Версия:** 1.0
**Дата:** 2026-03-22
**Основание:** docs/technical-specification-parts/work-catalog.md (v1.1)

---

## Содержание

1. [Обзор архитектуры](#1-обзор-архитектуры)
2. [Структура модулей и файлов](#2-структура-модулей-и-файлов)
3. [Сущности, Value Objects](#3-сущности-value-objects)
4. [Доменные репозитории](#4-доменные-репозитории)
5. [Use Cases](#5-use-cases)
6. [Доменные события и аудит-логирование](#6-доменные-события-и-аудит-логирование)
7. [Доменные исключения](#7-доменные-исключения)
8. [Persistence (Doctrine)](#8-persistence-doctrine)
9. [Infrastructure (HTTP-слой)](#9-infrastructure-http-слой)
10. [Зависимости между компонентами](#10-зависимости-между-компонентами)
11. [Порядок реализации](#11-порядок-реализации)
12. [Тестирование](#12-тестирование)

---

## 1. Обзор архитектуры

Домен Work Catalog реализуется как отдельный Bounded Context `WorkCatalog` в рамках существующей чистой архитектуры проекта. Структура следует принципам Clean Architecture и DDD:

- **Domain** -- сущности, Value Objects, интерфейсы репозиториев, доменные события, исключения
- **Application** -- Use Cases, InputDto, OutputDto, OutputPort-интерфейсы
- **Infrastructure** -- контроллеры, DTO запросов/ответов, Value Resolvers, Input Transformers, Presenters, реализации репозиториев, Event Listener для аудит-логирования

Persistence-слой (Doctrine Entity и Repository) размещается в домене `Persistence` -- архитектурный компромисс проекта.

Work Catalog -- upstream-контекст: предоставляет справочные данные (Rate, Role, Work) для других доменов (Timesheet, Identity, Reporting).

---

## 2. Структура модулей и файлов

### Bounded Context: WorkCatalog

```
app/src/
├── WorkCatalog/
│   ├── Domain/
│   │   ├── Entity/
│   │   │   ├── Work.php
│   │   │   ├── Role.php
│   │   │   └── Rate.php
│   │   ├── ValueObject/
│   │   │   ├── WorkId.php
│   │   │   ├── RoleId.php
│   │   │   ├── RateId.php
│   │   │   └── Money.php
│   │   ├── Repository/
│   │   │   ├── WorkRepositoryInterface.php
│   │   │   ├── RoleRepositoryInterface.php
│   │   │   └── RateRepositoryInterface.php
│   │   ├── Event/
│   │   │   ├── WorkCreated.php
│   │   │   ├── RoleCreated.php
│   │   │   └── RateCreated.php
│   │   └── Exception/
│   │       ├── WorkNotFoundException.php
│   │       ├── RoleNotFoundException.php
│   │       ├── RateNotFoundException.php
│   │       ├── DuplicateWorkNameException.php
│   │       ├── DuplicateRoleNameException.php
│   │       ├── WorkHasLinkedTicketsException.php
│   │       ├── RoleHasActiveRatesException.php
│   │       ├── RoleHasLinkedUsersException.php
│   │       ├── RateEffectiveFromImmutableException.php
│   │       └── EntityDeletedException.php
│   ├── Application/
│   │   ├── UseCase/
│   │   │   ├── CreateWorkUseCase.php
│   │   │   ├── DeleteWorkUseCase.php
│   │   │   ├── ListWorksUseCase.php
│   │   │   ├── CreateRoleUseCase.php
│   │   │   ├── DeleteRoleUseCase.php
│   │   │   ├── ListRolesUseCase.php
│   │   │   ├── CreateRateUseCase.php
│   │   │   ├── UpdateRateUseCase.php
│   │   │   ├── DeleteRateUseCase.php
│   │   │   └── ListRatesUseCase.php
│   │   ├── Dto/
│   │   │   ├── CreateWorkInputDto.php
│   │   │   ├── DeleteWorkInputDto.php
│   │   │   ├── ListWorksInputDto.php
│   │   │   ├── ListWorksOutputDto.php
│   │   │   ├── CreateRoleInputDto.php
│   │   │   ├── DeleteRoleInputDto.php
│   │   │   ├── ListRolesInputDto.php
│   │   │   ├── ListRolesOutputDto.php
│   │   │   ├── CreateRateInputDto.php
│   │   │   ├── UpdateRateInputDto.php
│   │   │   ├── DeleteRateInputDto.php
│   │   │   ├── ListRatesInputDto.php
│   │   │   └── ListRatesOutputDto.php
│   │   └── Port/
│   │       ├── ListWorksOutputPortInterface.php
│   │       ├── ListRolesOutputPortInterface.php
│   │       ├── ListRatesOutputPortInterface.php
│   │       ├── TicketExistenceByWorkCheckerInterface.php
│   │       ├── UserExistenceByRoleCheckerInterface.php
│   │       └── RateAppliedToTicketCheckerInterface.php
│   └── Infrastructure/
│       ├── Controller/
│       │   ├── CreateWorkController.php
│       │   ├── DeleteWorkController.php
│       │   ├── ListWorksController.php
│       │   ├── CreateRoleController.php
│       │   ├── DeleteRoleController.php
│       │   ├── ListRolesController.php
│       │   ├── CreateRateController.php
│       │   ├── UpdateRateController.php
│       │   ├── DeleteRateController.php
│       │   └── ListRatesController.php
│       ├── Dto/
│       │   ├── CreateWorkRequestDto.php
│       │   ├── CreateRoleRequestDto.php
│       │   ├── CreateRateRequestDto.php
│       │   ├── UpdateRateRequestDto.php
│       │   ├── ListWorksRequestDto.php
│       │   ├── ListRolesRequestDto.php
│       │   ├── ListRatesRequestDto.php
│       │   ├── WorkResponseDto.php
│       │   ├── RoleResponseDto.php
│       │   ├── RateResponseDto.php
│       │   ├── WorkListResponseDto.php
│       │   ├── RoleListResponseDto.php
│       │   └── RateListResponseDto.php
│       ├── ValueResolver/
│       │   ├── CreateWorkValueResolver.php
│       │   ├── CreateRoleValueResolver.php
│       │   ├── CreateRateValueResolver.php
│       │   ├── UpdateRateValueResolver.php
│       │   ├── ListWorksValueResolver.php
│       │   ├── ListRolesValueResolver.php
│       │   └── ListRatesValueResolver.php
│       ├── Transformer/
│       │   ├── CreateWorkInputTransformer.php
│       │   ├── DeleteWorkInputTransformer.php
│       │   ├── ListWorksInputTransformer.php
│       │   ├── CreateRoleInputTransformer.php
│       │   ├── DeleteRoleInputTransformer.php
│       │   ├── ListRolesInputTransformer.php
│       │   ├── CreateRateInputTransformer.php
│       │   ├── UpdateRateInputTransformer.php
│       │   ├── DeleteRateInputTransformer.php
│       │   └── ListRatesInputTransformer.php
│       ├── Presenter/
│       │   ├── HttpListWorksPresenter.php
│       │   ├── HttpListRolesPresenter.php
│       │   └── HttpListRatesPresenter.php
│       ├── EventListener/
│       │   └── AuditLogEventListener.php
│       └── Repository/
│           ├── DoctrineWorkRepository.php
│           ├── DoctrineRoleRepository.php
│           └── DoctrineRateRepository.php
```

### Persistence

```
app/src/
├── Persistence/
│   ├── Entity/
│   │   ├── Work.php
│   │   ├── Role.php
│   │   └── Rate.php
│   └── Repository/
│       ├── WorkRepository.php
│       ├── RoleRepository.php
│       └── RateRepository.php
```

---

## 3. Сущности, Value Objects

### 3.1 Value Objects

| Value Object | Расположение | Описание |
|---|---|---|
| `WorkId` | `app/src/WorkCatalog/Domain/ValueObject/WorkId.php` | Типизированный UUID-идентификатор вида работ |
| `RoleId` | `app/src/WorkCatalog/Domain/ValueObject/RoleId.php` | Типизированный UUID-идентификатор роли |
| `RateId` | `app/src/WorkCatalog/Domain/ValueObject/RateId.php` | Типизированный UUID-идентификатор ставки |
| `Money` | `app/src/WorkCatalog/Domain/ValueObject/Money.php` | Сумма и валюта (amount: decimal > 0, currency: string) |

Каждый ID Value Object:
- Принимает `string $value` в конструкторе
- Валидирует формат UUID
- Предоставляет методы `value(): string` и `equals(self $other): bool`
- Имеет статический метод `generate(): self` для создания нового UUID

`Money`:
- Принимает `string $amount` и `string $currency` в конструкторе
- Валидирует: `amount` строго положительный (> 0), `currency` непустая строка
- Предоставляет методы `amount(): string`, `currency(): string`, `equals(self $other): bool`
- `amount` хранится как `string` для точного представления decimal-значений

### 3.2 Domain Entities (Aggregate Roots)

#### Work

- **Расположение:** `app/src/WorkCatalog/Domain/Entity/Work.php`
- **Атрибуты:** `id: WorkId`, `name: string`, `description: ?string`, `deletedAt: ?DateTimeImmutable`
- **Фабричный метод `create(name, description)`:** валидирует, что `name` не пустой; устанавливает `deletedAt = null`
- **Фабричный метод `restore()`:** восстановление из хранилища без валидации
- **Метод `softDelete()`:** устанавливает `deletedAt` в текущее время
- **Метод `isDeleted(): bool`:** проверяет `deletedAt !== null`
- **Инварианты:** `name` не может быть пустым; Work не может быть помечена удалённой при наличии привязанных Ticket (проверяется в Use Case через Persistence)

#### Role

- **Расположение:** `app/src/WorkCatalog/Domain/Entity/Role.php`
- **Атрибуты:** `id: RoleId`, `name: string`, `description: ?string`, `deletedAt: ?DateTimeImmutable`
- **Фабричный метод `create(name, description)`:** валидирует, что `name` не пустой; устанавливает `deletedAt = null`
- **Фабричный метод `restore()`:** восстановление из хранилища без валидации
- **Метод `softDelete()`:** устанавливает `deletedAt` в текущее время
- **Метод `isDeleted(): bool`:** проверяет `deletedAt !== null`
- **Инварианты:** `name` не может быть пустым; Role не может быть помечена удалённой при наличии активных Rate или привязанных пользователей

#### Rate

- **Расположение:** `app/src/WorkCatalog/Domain/Entity/Rate.php`
- **Атрибуты:** `id: RateId`, `money: Money`, `effectiveFrom: DateTimeImmutable`, `roleId: ?RoleId`, `workId: ?WorkId`, `deletedAt: ?DateTimeImmutable`
- **Фабричный метод `create(money, effectiveFrom, roleId, workId)`:** валидирует `money` (через Value Object); устанавливает `deletedAt = null`
- **Фабричный метод `restore()`:** восстановление из хранилища без валидации
- **Метод `update(money, effectiveFrom, roleId, workId, isAppliedToTicket)`:** обновление допустимых полей; если `isAppliedToTicket === true` и `effectiveFrom` отличается от текущего -- бросает `RateEffectiveFromImmutableException`
- **Метод `softDelete()`:** устанавливает `deletedAt` в текущее время
- **Метод `isDeleted(): bool`:** проверяет `deletedAt !== null`
- **Инварианты:** `amount > 0` (обеспечивается Value Object `Money`); `effectiveFrom` не может быть изменён после применения Rate к Ticket

---

## 4. Доменные репозитории

### Интерфейсы (Domain)

| Интерфейс | Расположение | Ключевые методы |
|---|---|---|
| `WorkRepositoryInterface` | `app/src/WorkCatalog/Domain/Repository/` | `save()`, `findById(WorkId)`, `findByName(string): ?Work`, `findAll(criteria): array`, `countAll(criteria): int` |
| `RoleRepositoryInterface` | `app/src/WorkCatalog/Domain/Repository/` | `save()`, `findById(RoleId)`, `findByName(string): ?Role`, `findAll(criteria): array`, `countAll(criteria): int`, `countActiveRatesByRoleId(RoleId): int` |
| `RateRepositoryInterface` | `app/src/WorkCatalog/Domain/Repository/` | `save()`, `findById(RateId)`, `findAll(criteria): array`, `countAll(criteria): int` |

**Примечания:**
- `findByName` используется для проверки уникальности наименования при создании Work и Role
- `countActiveRatesByRoleId` считает только активные Rate (`deletedAt IS NULL`) -- для проверки возможности удаления Role
- `findAll` во всех репозиториях по умолчанию возвращает только активные (не удалённые, `deletedAt IS NULL`) записи
- Все методы оперируют доменными типами (Value Objects, Domain Entity)

### Application Port-интерфейсы (межконтекстные проверки)

| Интерфейс | Расположение | Описание |
|---|---|---|
| `TicketExistenceByWorkCheckerInterface` | `app/src/WorkCatalog/Application/Port/` | Проверка наличия Ticket с данной Work. Метод: `hasTicketsForWork(WorkId): bool` |
| `UserExistenceByRoleCheckerInterface` | `app/src/WorkCatalog/Application/Port/` | Проверка наличия пользователей с данной Role. Метод: `hasUsersByRole(RoleId): bool` |
| `RateAppliedToTicketCheckerInterface` | `app/src/WorkCatalog/Application/Port/` | Проверка, применена ли Rate хотя бы к одному Ticket. Метод: `isRateAppliedToTicket(RateId): bool` |

**Реализации** этих портов размещаются в `app/src/WorkCatalog/Infrastructure/` и выполняют прямые запросы к Persistence (таблицы `tickets`, `users`), без межсервисных вызовов -- все данные в одной БД.

### Реализации (Infrastructure)

Размещаются в `app/src/WorkCatalog/Infrastructure/Repository/`, используют composition с Doctrine Repository из `Persistence`. Выполняют маппинг Domain Entity <-> Doctrine Entity через методы `toOrmEntity()` и `toDomainEntity()`.

---

## 5. Use Cases

### Command Use Cases (создание/обновление/удаление)

| Use Case | Описание | InputDto | Зависимости |
|---|---|---|---|
| `CreateWorkUseCase` | Создание вида работ (UC-WC-01) | `name`, `?description` | `WorkRepositoryInterface` |
| `DeleteWorkUseCase` | Soft-delete вида работ (UC-WC-05) | `id` | `WorkRepositoryInterface`, `TicketExistenceByWorkCheckerInterface` |
| `CreateRoleUseCase` | Создание роли (UC-WC-02) | `name`, `?description` | `RoleRepositoryInterface` |
| `DeleteRoleUseCase` | Soft-delete роли (UC-WC-06) | `id` | `RoleRepositoryInterface`, `UserExistenceByRoleCheckerInterface` |
| `CreateRateUseCase` | Создание ставки (UC-WC-03) | `amount`, `currency`, `effectiveFrom`, `?roleId`, `?workId` | `RateRepositoryInterface`, `RoleRepositoryInterface`, `WorkRepositoryInterface` |
| `UpdateRateUseCase` | Обновление ставки (UC-WC-04) | `id`, `?amount`, `?currency`, `?effectiveFrom`, `?roleId`, `?workId` | `RateRepositoryInterface`, `RoleRepositoryInterface`, `WorkRepositoryInterface`, `RateAppliedToTicketCheckerInterface` |
| `DeleteRateUseCase` | Soft-delete ставки (UC-WC-07) | `id` | `RateRepositoryInterface` |

### Query Use Cases (списки)

| Use Case | Описание | InputDto | OutputDto | Зависимости |
|---|---|---|---|---|
| `ListWorksUseCase` | Список видов работ (UC-WC-08) | `page`, `perPage` | `items[]`, `total`, `page`, `perPage` | `WorkRepositoryInterface`, `ListWorksOutputPortInterface` |
| `ListRolesUseCase` | Список ролей | `page`, `perPage` | `items[]`, `total`, `page`, `perPage` | `RoleRepositoryInterface`, `ListRolesOutputPortInterface` |
| `ListRatesUseCase` | Список ставок | `?roleId`, `?workId`, `page`, `perPage` | `items[]`, `total`, `page`, `perPage` | `RateRepositoryInterface`, `ListRatesOutputPortInterface` |

### Детали Use Cases

**CreateWorkUseCase:**
1. Проверка уникальности `name` через `WorkRepositoryInterface::findByName()`
2. При дубликате -- `DuplicateWorkNameException`
3. Вызов `Work::create(name, description)`
4. Публикация события `WorkCreated`
5. Сохранение через `WorkRepositoryInterface::save()`

**DeleteWorkUseCase (Soft-delete):**
1. Поиск Work по ID; при отсутствии -- `WorkNotFoundException`
2. Проверка: Work не помечена удалённой; при нарушении -- `EntityDeletedException`
3. Проверка: `TicketExistenceByWorkCheckerInterface::hasTicketsForWork()` возвращает `false`
4. При нарушении -- `WorkHasLinkedTicketsException`
5. Вызов `Work::softDelete()`
6. Сохранение через `WorkRepositoryInterface::save()`

**CreateRoleUseCase:**
1. Проверка уникальности `name` через `RoleRepositoryInterface::findByName()`
2. При дубликате -- `DuplicateRoleNameException`
3. Вызов `Role::create(name, description)`
4. Публикация события `RoleCreated`
5. Сохранение через `RoleRepositoryInterface::save()`

**DeleteRoleUseCase (Soft-delete):**
1. Поиск Role по ID; при отсутствии -- `RoleNotFoundException`
2. Проверка: Role не помечена удалённой; при нарушении -- `EntityDeletedException`
3. Проверка: `RoleRepositoryInterface::countActiveRatesByRoleId()` возвращает `0`
4. При нарушении -- `RoleHasActiveRatesException`
5. Проверка: `UserExistenceByRoleCheckerInterface::hasUsersByRole()` возвращает `false`
6. При нарушении -- `RoleHasLinkedUsersException`
7. Вызов `Role::softDelete()`
8. Сохранение через `RoleRepositoryInterface::save()`

**CreateRateUseCase:**
1. Валидация `amount > 0` (обеспечивается Value Object `Money`)
2. Если `roleId` передан -- проверка существования Role и что она не удалена
3. Если `workId` передан -- проверка существования Work и что она не удалена
4. Вызов `Rate::create(money, effectiveFrom, roleId, workId)`
5. Публикация события `RateCreated`
6. Сохранение через `RateRepositoryInterface::save()`

**UpdateRateUseCase (PUT-семантика):**
1. Поиск Rate по ID; при отсутствии -- `RateNotFoundException`
2. Проверка: Rate не помечена удалённой; при нарушении -- `EntityDeletedException`
3. Проверка `isRateAppliedToTicket` через `RateAppliedToTicketCheckerInterface`
4. Если `roleId` передан -- проверка существования Role и что она не удалена
5. Если `workId` передан -- проверка существования Work и что она не удалена
6. Применение правил PUT-семантики: переданные поля обновляются; не переданные -- остаются без изменений
7. Вызов `Rate::update(...)` -- при попытке изменить `effectiveFrom` у Rate, применённой к Ticket -- `RateEffectiveFromImmutableException`
8. Сохранение через `RateRepositoryInterface::save()`

**DeleteRateUseCase (Soft-delete):**
1. Поиск Rate по ID; при отсутствии -- `RateNotFoundException`
2. Проверка: Rate не помечена удалённой; при нарушении -- `EntityDeletedException`
3. Вызов `Rate::softDelete()`
4. Сохранение через `RateRepositoryInterface::save()`

**ListWorksUseCase / ListRolesUseCase / ListRatesUseCase:**
1. Маппинг фильтров из InputDto
2. Запрос к репозиторию с фильтрацией и пагинацией
3. Все списки возвращают только активные (не удалённые, `deletedAt IS NULL`) записи
4. Маппинг доменных объектов в OutputDto
5. Передача через `$this->presenter->present(OutputDto)`

**Примечание для ListRatesUseCase:** согласно НФТ-У-02, ответ содержит наименования связанных Role и Work (`roleName`, `workName`), а не только их идентификаторы. Для получения наименований используются дополнительные запросы к `RoleRepositoryInterface` и `WorkRepositoryInterface` или JOIN на уровне Persistence.

---

## 6. Доменные события и аудит-логирование

### 6.1 Доменные события

| Событие | Расположение | Поля | Триггер |
|---|---|---|---|
| `WorkCreated` | `app/src/WorkCatalog/Domain/Event/` | `WorkId $workId`, `string $name` | `CreateWorkUseCase` |
| `RoleCreated` | `app/src/WorkCatalog/Domain/Event/` | `RoleId $roleId`, `string $name` | `CreateRoleUseCase` |
| `RateCreated` | `app/src/WorkCatalog/Domain/Event/` | `RateId $rateId`, `string $amount`, `string $currency`, `string $effectiveFrom`, `?string $roleId`, `?string $workId` | `CreateRateUseCase` |

Все события -- `final readonly class`, содержат `DateTimeImmutable $occurredAt`.

**Примечание:** ТЗ определяет только три события (WorkCreated, RoleCreated, RateCreated). При необходимости аудит-логирования операций Update и Delete рекомендуется добавить дополнительные события (см. `docs/plan/work-catalog-issues.md`, проблема 1).

### 6.2 Аудит-логирование (НФТ-Б-06)

**Механизм:** Event Listener на доменные события, запись через Symfony Logger (Monolog).

**Расположение:** `app/src/WorkCatalog/Infrastructure/EventListener/AuditLogEventListener.php`

**Принцип работы:**
1. При публикации доменного события Event Listener перехватывает событие
2. Формирует структурированную запись аудит-лога
3. Записывает через `LoggerInterface` (Monolog)

**Состав записи аудит-лога:**
- Идентификатор пользователя, выполнившего операцию
- Тип действия: `create`, `update`, `delete`
- Тип сущности и её идентификатор
- Временная метка (UTC)
- Результат операции (`success` / `failure`)

**Место хранения:** стандартный лог-файл Symfony (stdout). Отдельная таблица `audit_log` не создаётся.

---

## 7. Доменные исключения

| Исключение | Когда выбрасывается |
|---|---|
| `WorkNotFoundException` | Work не найдена по ID |
| `RoleNotFoundException` | Role не найдена по ID |
| `RateNotFoundException` | Rate не найдена по ID |
| `DuplicateWorkNameException` | Попытка создать Work с неуникальным наименованием |
| `DuplicateRoleNameException` | Попытка создать Role с неуникальным наименованием |
| `WorkHasLinkedTicketsException` | Попытка soft-delete Work, используемой в Ticket |
| `RoleHasActiveRatesException` | Попытка soft-delete Role с привязанными активными Rate |
| `RoleHasLinkedUsersException` | Попытка soft-delete Role с привязанными пользователями |
| `RateEffectiveFromImmutableException` | Попытка изменить `effectiveFrom` у Rate, применённой к Ticket |
| `EntityDeletedException` | Попытка обновить или повторно удалить сущность, помеченную удалённой (`deletedAt IS NOT NULL`) |

Все исключения наследуют `\DomainException`.

---

## 8. Persistence (Doctrine)

### Doctrine Entities

#### `Persistence\Entity\Work`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `name` | `string(255)` | NOT NULL, UNIQUE |
| `description` | `text` | NULLABLE |
| `deleted_at` | `datetime_immutable` | NULLABLE |
| `created_at` | `datetime_immutable` | NOT NULL |
| `updated_at` | `datetime_immutable` | NULLABLE |

**Индексы:** на `deleted_at` (для фильтрации активных записей); UNIQUE на `name`.

#### `Persistence\Entity\Role`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `name` | `string(255)` | NOT NULL, UNIQUE |
| `description` | `text` | NULLABLE |
| `deleted_at` | `datetime_immutable` | NULLABLE |
| `created_at` | `datetime_immutable` | NOT NULL |
| `updated_at` | `datetime_immutable` | NULLABLE |

**Индексы:** на `deleted_at`; UNIQUE на `name`.

#### `Persistence\Entity\Rate`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `amount` | `decimal(12,2)` | NOT NULL |
| `currency` | `string(3)` | NOT NULL |
| `effective_from` | `date_immutable` | NOT NULL |
| `role_id` | `guid` | NULLABLE, FK -> roles, INDEX |
| `work_id` | `guid` | NULLABLE, FK -> works, INDEX |
| `deleted_at` | `datetime_immutable` | NULLABLE |
| `created_at` | `datetime_immutable` | NOT NULL |
| `updated_at` | `datetime_immutable` | NULLABLE |

**Индексы:** на `deleted_at`; на `role_id`; на `work_id`.

### Doctrine Repositories

Каждый наследует `ServiceEntityRepository`, содержит:
- `save(Entity, flush)`, `remove(Entity, flush)`
- Методы поиска с фильтрацией
- `WorkRepository`: `findByName(string): ?Work`, `findActiveAll(criteria): array`, `countActive(criteria): int`
- `RoleRepository`: `findByName(string): ?Role`, `findActiveAll(criteria): array`, `countActive(criteria): int`, `countActiveRatesByRoleId(string): int`
- `RateRepository`: `findActiveAll(criteria): array`, `countActive(criteria): int`, `findByRoleId(string): array`, `findByWorkId(string): array`
- Все `findActive*` методы фильтруют по `deleted_at IS NULL`
- Методы с пагинацией (QueryBuilder + `setFirstResult()`/`setMaxResults()`)

---

## 9. Infrastructure (HTTP-слой)

### API-маршруты

| Метод | Путь | Контроллер | Имя маршрута | Права |
|---|---|---|---|---|
| POST | `/api/work-catalog/works` | `CreateWorkController` | `work_catalog_create_work` | Admin |
| GET | `/api/work-catalog/works` | `ListWorksController` | `work_catalog_list_works` | Admin, Manager, Employee |
| DELETE | `/api/work-catalog/works/{id}` | `DeleteWorkController` | `work_catalog_delete_work` | Admin |
| POST | `/api/work-catalog/roles` | `CreateRoleController` | `work_catalog_create_role` | Admin |
| GET | `/api/work-catalog/roles` | `ListRolesController` | `work_catalog_list_roles` | Admin |
| DELETE | `/api/work-catalog/roles/{id}` | `DeleteRoleController` | `work_catalog_delete_role` | Admin |
| POST | `/api/work-catalog/rates` | `CreateRateController` | `work_catalog_create_rate` | Admin |
| GET | `/api/work-catalog/rates` | `ListRatesController` | `work_catalog_list_rates` | Admin |
| PUT | `/api/work-catalog/rates/{id}` | `UpdateRateController` | `work_catalog_update_rate` | Admin |
| DELETE | `/api/work-catalog/rates/{id}` | `DeleteRateController` | `work_catalog_delete_rate` | Admin |

### Request DTO с валидацией

**CreateWorkRequestDto:**
- `name: string` -- `#[NotBlank]`, `#[Length(max: 255)]`
- `description: ?string` -- `#[Length(max: 1000)]`

**CreateRoleRequestDto:**
- `name: string` -- `#[NotBlank]`, `#[Length(max: 255)]`
- `description: ?string` -- `#[Length(max: 1000)]`

**CreateRateRequestDto:**
- `amount: string` -- `#[NotBlank]`, `#[Positive]`
- `currency: string` -- `#[NotBlank]`, `#[Length(max: 3)]`
- `effectiveFrom: string` -- `#[NotBlank]`, `#[Date]`
- `roleId: ?string` -- `#[Uuid]`
- `workId: ?string` -- `#[Uuid]`

**UpdateRateRequestDto:**
- `amount: ?string` -- `#[Positive]` (если передан)
- `currency: ?string` -- `#[Length(max: 3)]`
- `effectiveFrom: ?string` -- `#[Date]`
- `roleId: ?string` -- `#[Uuid]`
- `workId: ?string` -- `#[Uuid]`

**ListWorksRequestDto** (GET, query-параметры):
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

**ListRolesRequestDto** (GET, query-параметры):
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

**ListRatesRequestDto** (GET, query-параметры):
- `roleId: ?string` -- `#[Uuid]`
- `workId: ?string` -- `#[Uuid]`
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

### Response DTO

Каждый Response DTO содержит OpenAPI-атрибуты (`#[OA\Schema]`, `#[OA\Property]`).

**WorkResponseDto:** `id`, `name`, `description`
**RoleResponseDto:** `id`, `name`, `description`
**RateResponseDto:** `id`, `amount`, `currency`, `effectiveFrom`, `roleId`, `roleName`, `workId`, `workName`

List Response DTO содержат `items[]`, `total`, `page`, `perPage`.

**Важно:** согласно требованию раздела 6 ТЗ, Rate List возвращает наименования связанных Role и Work (`roleName`, `workName`), а не только их идентификаторы.

---

## 10. Зависимости между компонентами

### Граф зависимостей (направление: зависимый -> от чего зависит)

```
Domain Layer (нет внешних зависимостей):
  Entity -> ValueObject, Exception
  Repository Interface -> Entity, ValueObject

Application Layer (зависит от Domain):
  UseCase -> Repository Interface, Entity, ValueObject, Exception, Port Interface
  InputDto -> (только примитивы, нет зависимостей)
  OutputDto -> (только примитивы, нет зависимостей)
  OutputPort -> OutputDto
  Port Interface -> ValueObject (для типобезопасных параметров)

Infrastructure Layer (зависит от Domain + Application):
  Controller -> UseCase, Transformer, Presenter, RequestDto
  Transformer -> RequestDto, InputDto
  Presenter -> OutputDto, OutputPort, ResponseDto
  ValueResolver -> AbstractJsonValueResolver/AbstractValueResolver, RequestDto
  DoctrineRepository -> Domain Repository Interface, Domain Entity, Domain VO, Persistence Entity, Persistence Repository
  Port Implementation -> Persistence Repository, Domain ValueObject
  AuditLogEventListener -> Domain Event, LoggerInterface, TokenStorageInterface

Persistence Layer (зависит от Doctrine):
  Doctrine Entity -> ORM Mapping
  Doctrine Repository -> Doctrine Entity, ServiceEntityRepository
```

### Зависимости от существующей инфраструктуры

- `App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver` -- для POST/PUT-запросов
- `App\Shared\Infrastructure\ValueResolver\AbstractValueResolver` -- для GET-запросов (List)
- `App\Persistence\Entity\*` и `App\Persistence\Repository\*` -- Doctrine-слой
- Symfony Framework: Route, AbstractController, JsonResponse, Validator, Serializer, EventDispatcher, Security, Logger (Monolog)

### Межконтекстные зависимости

- **TimesheetContext -> WorkCatalog:** TimesheetContext использует `workId` для привязки Ticket к Work; фиксирует `rateSnapshot` при создании Ticket
- **IdentityContext -> WorkCatalog:** IdentityContext использует `roleId` для профиля пользователя
- **WorkCatalog -> Persistence:** при удалении Work/Role проверка наличия Ticket/User выполняется через прямой запрос к Persistence, без межсервисных вызовов

---

## 11. Порядок реализации

### Этап 1: Domain Layer

**Шаг 1.1:** Value Objects
- `WorkId`, `RoleId`, `RateId`, `Money`

**Шаг 1.2:** Доменные исключения
- Все исключения из раздела 7

**Шаг 1.3:** Доменные события
- `WorkCreated`, `RoleCreated`, `RateCreated`

**Шаг 1.4:** Domain Entities
- `Work` (с `deletedAt`, методами `softDelete()`, `create()`, `restore()`)
- `Role` (с `deletedAt`, методами `softDelete()`, `create()`, `restore()`)
- `Rate` (с `deletedAt`, методами `softDelete()`, `create()`, `restore()`, `update()`)

**Шаг 1.5:** Интерфейсы репозиториев
- `WorkRepositoryInterface`
- `RoleRepositoryInterface` (с `countActiveRatesByRoleId`)
- `RateRepositoryInterface`

### Этап 2: Persistence Layer

**Шаг 2.1:** Doctrine Entities
- `Persistence\Entity\Work`, `Role`, `Rate`

**Шаг 2.2:** Doctrine Repositories
- `Persistence\Repository\WorkRepository`, `RoleRepository`, `RateRepository`

**Шаг 2.3:** Миграции
- Генерация через `doctrine:migrations:diff`

### Этап 3: Application Layer

**Шаг 3.1:** Port-интерфейсы (межконтекстные проверки)
- `TicketExistenceByWorkCheckerInterface`, `UserExistenceByRoleCheckerInterface`, `RateAppliedToTicketCheckerInterface`

**Шаг 3.2:** InputDto и OutputDto
- Все DTO из раздела 5

**Шаг 3.3:** OutputPort-интерфейсы
- `ListWorksOutputPortInterface`, `ListRolesOutputPortInterface`, `ListRatesOutputPortInterface`

**Шаг 3.4:** Command Use Cases -- создание
- `CreateWorkUseCase`, `CreateRoleUseCase`, `CreateRateUseCase`

**Шаг 3.5:** Command Use Cases -- обновление
- `UpdateRateUseCase`

**Шаг 3.6:** Command Use Cases -- удаление (все soft-delete)
- `DeleteWorkUseCase`, `DeleteRoleUseCase`, `DeleteRateUseCase`

**Шаг 3.7:** Query Use Cases
- `ListWorksUseCase`, `ListRolesUseCase`, `ListRatesUseCase`

### Этап 4: Infrastructure Layer

**Шаг 4.1:** Реализации доменных репозиториев
- `DoctrineWorkRepository`, `DoctrineRoleRepository`, `DoctrineRateRepository`

**Шаг 4.2:** Реализации Port-интерфейсов
- Реализации `TicketExistenceByWorkCheckerInterface`, `UserExistenceByRoleCheckerInterface`, `RateAppliedToTicketCheckerInterface` -- прямые запросы к Persistence

**Шаг 4.3:** Request DTO
- Все Request DTO с атрибутами валидации

**Шаг 4.4:** Response DTO
- Все Response DTO с OpenAPI-атрибутами

**Шаг 4.5:** Value Resolvers
- По одному на каждый Request DTO

**Шаг 4.6:** Input Transformers
- По одному на каждый Use Case

**Шаг 4.7:** Presenters
- `HttpListWorksPresenter`, `HttpListRolesPresenter`, `HttpListRatesPresenter`

**Шаг 4.8:** Controllers
- Все 10 контроллеров (3 сущности x операции)

**Шаг 4.9:** Event Listener для аудит-логирования
- `AuditLogEventListener` -- подписка на доменные события, запись через Monolog

**Шаг 4.10:** DI-конфигурация
- Alias-привязки интерфейсов к реализациям в `config/services.php`
- Регистрация Event Listener

### Этап 5: Тестирование

**Шаг 5.1:** Unit-тесты Domain (Entity, VO)
**Шаг 5.2:** Unit-тесты Application (Use Cases)
**Шаг 5.3:** Unit-тесты Infrastructure (Transformers, Presenters, Repositories)
**Шаг 5.4:** Интеграционные тесты (Doctrine Repositories)
**Шаг 5.5:** Функциональные тесты (Controllers через WebTestCase)

---

## 12. Тестирование

### Unit-тесты Domain

| Тест | Что проверяется |
|---|---|
| `WorkTest` | Создание с валидным именем; отклонение пустого имени; `restore()`; `softDelete()`; `isDeleted()` |
| `RoleTest` | Создание с валидным именем; отклонение пустого имени; `restore()`; `softDelete()`; `isDeleted()` |
| `RateTest` | Создание с валидными данными; `restore()`; `update()` с допустимыми/недопустимыми изменениями; `softDelete()`; `isDeleted()` |
| `MoneyTest` | Создание с валидной суммой и валютой; отклонение нулевого/отрицательного amount; отклонение пустой валюты; `equals()` |
| ID Value Objects | Валидация UUID; `equals()`; `generate()` |

### Unit-тесты Application

| Тест | Что проверяется |
|---|---|
| `CreateWorkUseCaseTest` | Вызов `save()` с доменным объектом; маппинг InputDto -> VO; дубликат имени -- `DuplicateWorkNameException`; публикация `WorkCreated` |
| `DeleteWorkUseCaseTest` | Успешный soft-delete; выброс `WorkNotFoundException`; выброс `WorkHasLinkedTicketsException`; выброс `EntityDeletedException` при удалённой Work |
| `CreateRoleUseCaseTest` | Вызов `save()`; дубликат имени -- `DuplicateRoleNameException`; публикация `RoleCreated` |
| `DeleteRoleUseCaseTest` | Успешный soft-delete; выброс `RoleNotFoundException`; выброс `RoleHasActiveRatesException`; выброс `RoleHasLinkedUsersException`; выброс `EntityDeletedException` |
| `CreateRateUseCaseTest` | Создание с roleId/workId; проверка существования Role/Work; отклонение удалённых Role/Work; публикация `RateCreated` |
| `UpdateRateUseCaseTest` | Успешное обновление; выброс `RateNotFoundException`; выброс `RateEffectiveFromImmutableException`; выброс `EntityDeletedException` |
| `DeleteRateUseCaseTest` | Успешный soft-delete; выброс `RateNotFoundException`; выброс `EntityDeletedException` |
| `ListWorksUseCaseTest` | Вызов `present()` с корректным OutputDto; пустой результат |
| `ListRolesUseCaseTest` | Вызов `present()` с корректным OutputDto; пустой результат |
| `ListRatesUseCaseTest` | Фильтрация по roleId/workId; пагинация; вызов `present()` |

### Unit-тесты Infrastructure

| Тест | Что проверяется |
|---|---|
| `DoctrineWorkRepositoryTest` | Маппинг Domain Entity <-> Doctrine Entity; делегирование save/find |
| `DoctrineRoleRepositoryTest` | Маппинг; делегирование; `countActiveRatesByRoleId` |
| `DoctrineRateRepositoryTest` | Маппинг; делегирование; фильтрация по roleId/workId |
| Transformer-тесты | Маппинг Request DTO -> InputDto для каждого Transformer |
| Presenter-тесты | Маппинг OutputDto -> Response DTO для каждого Presenter |
| `AuditLogEventListenerTest` | Перехват событий; формирование корректной записи лога |

### Интеграционные тесты

| Тест | Что проверяется |
|---|---|
| `WorkRepositoryTest` | CRUD-операции; фильтрация по `deleted_at`; уникальность `name` |
| `RoleRepositoryTest` | CRUD-операции; `countActiveRatesByRoleId` |
| `RateRepositoryTest` | CRUD-операции; фильтрация по `role_id`/`work_id` |

### Функциональные тесты

| Тест | Что проверяется |
|---|---|
| `CreateWorkControllerTest` | HTTP 201; валидация; дубликат имени -- HTTP 409 |
| `ListWorksControllerTest` | HTTP 200; пагинация; пустой список |
| `DeleteWorkControllerTest` | HTTP 204; Work не найдена -- HTTP 404; Work с Ticket -- HTTP 409 |
| `CreateRoleControllerTest` | HTTP 201; валидация; дубликат имени -- HTTP 409 |
| `ListRolesControllerTest` | HTTP 200; пагинация |
| `DeleteRoleControllerTest` | HTTP 204; ошибки |
| `CreateRateControllerTest` | HTTP 201; валидация amount/currency/effectiveFrom |
| `ListRatesControllerTest` | HTTP 200; фильтрация по roleId/workId |
| `UpdateRateControllerTest` | HTTP 200; запрет изменения effectiveFrom -- HTTP 409 |
| `DeleteRateControllerTest` | HTTP 204; ошибки |
