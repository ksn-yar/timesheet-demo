# План реализации домена Timesheet

**Версия:** 1.2
**Дата:** 2026-03-22
**Основание:** docs/technical-specification-parts/timesheet.md (v1.0)

---

## Содержание

1. [Обзор архитектуры](#1-обзор-архитектуры)
2. [Структура модулей и файлов](#2-структура-модулей-и-файлов)
3. [Enum, Value Objects, Domain Entities](#3-enum-value-objects-domain-entities)
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

Домен Timesheet реализуется как отдельный Bounded Context `Timesheet` в рамках существующей чистой архитектуры проекта. Структура следует принципам Clean Architecture и DDD:

- **Domain** -- сущности, Value Objects, Enum, интерфейсы репозиториев, доменные события, исключения
- **Application** -- Use Cases, InputDto, OutputDto, OutputPort-интерфейсы, Port-интерфейсы для межконтекстного взаимодействия
- **Infrastructure** -- контроллеры, DTO запросов/ответов, Value Resolvers, Input Transformers, Presenters, реализации репозиториев, Event Listener для аудит-логирования

Persistence-слой (Doctrine Entity и Repository) размещается в домене `Persistence` -- архитектурный компромисс проекта.

Timesheet -- ядро системы (core domain) и downstream-контекст: потребляет данные из ProjectManagement (Task), WorkCatalog (Work, Rate) и Identity (User/Employee). Является upstream-поставщиком для Reporting.

### Принятые решения по открытым вопросам ТЗ

| # | Вопрос | Принятое решение | Обоснование |
|---|--------|-----------------|-------------|
| 1 | Поле `externalId` в Ticket | Добавляем `externalId: ?string` -- условно обязательное (заполняется только для Imported Ticket) | Без `externalId` невозможно реализовать проверку дубликатов при импорте (ИН-20) |
| 2 | Поведение при отсутствии Rate | `rateSnapshot = 0` -- Ticket создаётся с нулевой ставкой | Сотрудник не должен быть заблокирован из-за отсутствия тарифной конфигурации |
| 3 | Алгоритм определения Rate | Делегируется в `RateProviderInterface`; реализация использует приоритетную цепочку (Work+Role > Work > Role > базовая) | Определяется на стороне WorkCatalog, Timesheet просто вызывает Port |
| 4 | Операция удаления Ticket | Не реализуется в первой версии | ТЗ не определяет удаление; с точки зрения аудита запрет на удаление оправдан |
| 5 | Фильтрация по `projectId`/`crId` | JOIN через Persistence (вариант A) | Прагматичный подход для монолита с единой БД |
| 6 | Нумерация UC | Используем нумерацию из ТЗ как каноническую | ТЗ -- первичный источник |
| 7 | Операция `GET /timesheet/tickets/{id}` | Добавляем `GetTicketUseCase` и эндпоинт `GET /timesheet/tickets/{id}` | Необходимо для предзагрузки данных при редактировании |
| 8 | Именование событий (множественное число) | Сохраняем `TicketsAdded` из ТЗ; добавляем `TicketUpdated`, `ImportPolicyCreated`, `ImportPolicyUpdated` | Следуем ТЗ для существующих событий; добавляем недостающие для аудит-логирования |
| 9 | Изменение `taskId` при редактировании | Не разрешаем -- следуем ТЗ (UC-TS-02) | ТЗ явно не включает `taskId` в список изменяемых полей |
| 10 | Manager и редактирование Ticket | Manager наблюдает, но не редактирует | Соответствует матрице прав в ТЗ |
| 11 | Сопоставление Employee/Task/Work при импорте | Добавляем `EmployeeResolverInterface`, `TaskResolverInterface`, `WorkResolverInterface` -- Port-интерфейсы для разрешения внешних идентификаторов во внутренние UUID | `mappingRules` определяет `matchBy: email\|externalId\|name` -- для сопоставления нужны специализированные Port-ы, а не только `ExistenceChecker` |
| 12 | `externalId` в TicketResponseDto | Добавляем `externalId` в `TicketResponseDto` | Для Imported Ticket внешний идентификатор полезен при отладке и аудите |

---

## 2. Структура модулей и файлов

### Bounded Context: Timesheet

```
app/src/
├── Timesheet/
│   ├── Domain/
│   │   ├── Entity/
│   │   │   ├── Ticket.php
│   │   │   └── ImportPolicy.php
│   │   ├── ValueObject/
│   │   │   ├── TicketId.php
│   │   │   ├── ImportPolicyId.php
│   │   │   ├── ImportLogEntry.php
│   │   │   └── ImportSummary.php
│   │   ├── Enum/
│   │   │   ├── TicketType.php
│   │   │   └── ImportLogEntryStatus.php
│   │   ├── Repository/
│   │   │   ├── TicketRepositoryInterface.php
│   │   │   └── ImportPolicyRepositoryInterface.php
│   │   ├── Event/
│   │   │   ├── TicketsAdded.php
│   │   │   ├── TicketUpdated.php
│   │   │   ├── TicketsImported.php
│   │   │   ├── ImportPolicyCreated.php
│   │   │   └── ImportPolicyUpdated.php
│   │   └── Exception/
│   │       ├── TicketNotFoundException.php
│   │       ├── TicketNotEditableException.php
│   │       ├── TicketOwnershipViolationException.php
│   │       ├── ImportPolicyNotFoundException.php
│   │       ├── ImportPolicyNotActiveException.php
│   │       ├── ImportPolicyConflictException.php
│   │       └── InvalidTicketHoursException.php
│   ├── Application/
│   │   ├── UseCase/
│   │   │   ├── CreateManualTicketUseCase.php
│   │   │   ├── UpdateManualTicketUseCase.php
│   │   │   ├── GetTicketUseCase.php
│   │   │   ├── ListTicketsUseCase.php
│   │   │   ├── CreateImportPolicyUseCase.php
│   │   │   ├── UpdateImportPolicyUseCase.php
│   │   │   ├── ListImportPoliciesUseCase.php
│   │   │   └── RunImportUseCase.php
│   │   ├── Dto/
│   │   │   ├── CreateManualTicketInputDto.php
│   │   │   ├── UpdateManualTicketInputDto.php
│   │   │   ├── GetTicketInputDto.php
│   │   │   ├── ListTicketsInputDto.php
│   │   │   ├── ListTicketsOutputDto.php
│   │   │   ├── CreateImportPolicyInputDto.php
│   │   │   ├── UpdateImportPolicyInputDto.php
│   │   │   ├── ListImportPoliciesInputDto.php
│   │   │   ├── ListImportPoliciesOutputDto.php
│   │   │   ├── RunImportInputDto.php
│   │   │   └── RunImportOutputDto.php
│   │   └── Port/
│   │       ├── ListTicketsOutputPortInterface.php
│   │       ├── ListImportPoliciesOutputPortInterface.php
│   │       ├── RunImportOutputPortInterface.php
│   │       ├── TaskExistenceCheckerInterface.php
│   │       ├── WorkExistenceCheckerInterface.php
│   │       ├── RateProviderInterface.php
│   │       ├── CurrentUserProviderInterface.php
│   │       ├── ExternalDataFetcherInterface.php
│   │       ├── EmployeeResolverInterface.php
│   │       ├── TaskResolverInterface.php
│   │       └── WorkResolverInterface.php
│   └── Infrastructure/
│       ├── Http/
│       │   └── Controller/
│       │       ├── CreateManualTicketController.php
│       │       ├── UpdateManualTicketController.php
│       │       ├── GetTicketController.php
│       │       ├── ListTicketsController.php
│       │       ├── CreateImportPolicyController.php
│       │       ├── UpdateImportPolicyController.php
│       │       ├── ListImportPoliciesController.php
│       │       └── RunImportController.php
│       ├── Dto/
│       │   ├── CreateManualTicketRequestDto.php
│       │   ├── UpdateManualTicketRequestDto.php
│       │   ├── ListTicketsRequestDto.php
│       │   ├── CreateImportPolicyRequestDto.php
│       │   ├── UpdateImportPolicyRequestDto.php
│       │   ├── ListImportPoliciesRequestDto.php
│       │   ├── RunImportRequestDto.php
│       │   ├── TicketResponseDto.php
│       │   ├── TicketListResponseDto.php
│       │   ├── ImportPolicyResponseDto.php
│       │   ├── ImportPolicyListResponseDto.php
│       │   └── ImportSummaryResponseDto.php
│       ├── ValueResolver/
│       │   ├── CreateManualTicketValueResolver.php
│       │   ├── UpdateManualTicketValueResolver.php
│       │   ├── ListTicketsValueResolver.php
│       │   ├── CreateImportPolicyValueResolver.php
│       │   ├── UpdateImportPolicyValueResolver.php
│       │   ├── ListImportPoliciesValueResolver.php
│       │   └── RunImportValueResolver.php
│       ├── Transformer/
│       │   ├── CreateManualTicketInputTransformer.php
│       │   ├── UpdateManualTicketInputTransformer.php
│       │   ├── GetTicketInputTransformer.php
│       │   ├── ListTicketsInputTransformer.php
│       │   ├── CreateImportPolicyInputTransformer.php
│       │   ├── UpdateImportPolicyInputTransformer.php
│       │   ├── ListImportPoliciesInputTransformer.php
│       │   └── RunImportInputTransformer.php
│       ├── Presenter/
│       │   ├── HttpListTicketsPresenter.php
│       │   ├── HttpListImportPoliciesPresenter.php
│       │   └── HttpRunImportPresenter.php
│       ├── EventListener/
│       │   └── AuditLogEventListener.php
│       ├── Repository/
│       │   ├── TicketRepository.php
│       │   └── ImportPolicyRepository.php
│       └── Port/
│           ├── DoctrineTaskExistenceChecker.php
│           ├── DoctrineWorkExistenceChecker.php
│           ├── DoctrineRateProvider.php
│           ├── SecurityCurrentUserProvider.php
│           ├── NullExternalDataFetcher.php
│           ├── DoctrineEmployeeResolver.php
│           ├── DoctrineTaskResolver.php
│           └── DoctrineWorkResolver.php
```

### Persistence

```
app/src/
├── Persistence/
│   ├── Entity/
│   │   ├── Ticket.php
│   │   └── ImportPolicy.php
│   └── Repository/
│       ├── TicketRepository.php
│       └── ImportPolicyRepository.php
```

---

## 3. Enum, Value Objects, Domain Entities

### 3.1 Enum

#### TicketType

- **Расположение:** `app/src/Timesheet/Domain/Enum/TicketType.php`
- **Тип:** `enum TicketType: string`
- **Кейсы:** `Manual = 'manual'`, `Imported = 'imported'`
- **Методы:** `getLabel(): string` (русскоязычные метки: "Ручной", "Импортированный")

#### ImportLogEntryStatus

- **Расположение:** `app/src/Timesheet/Domain/Enum/ImportLogEntryStatus.php`
- **Тип:** `enum ImportLogEntryStatus: string`
- **Кейсы:** `Imported = 'imported'`, `Duplicate = 'duplicate'`, `Error = 'error'`
- **Методы:** `getLabel(): string` (русскоязычные метки: "Импортирован", "Дубликат", "Ошибка")

### 3.2 Value Objects

| Value Object | Расположение | Описание |
|---|---|---|
| `TicketId` | `app/src/Timesheet/Domain/ValueObject/TicketId.php` | Типизированный UUID-идентификатор Ticket |
| `ImportPolicyId` | `app/src/Timesheet/Domain/ValueObject/ImportPolicyId.php` | Типизированный UUID-идентификатор Import Policy |
| `ImportLogEntry` | `app/src/Timesheet/Domain/ValueObject/ImportLogEntry.php` | Запись о результате обработки одной записи при импорте |
| `ImportSummary` | `app/src/Timesheet/Domain/ValueObject/ImportSummary.php` | Итоговая сводка результата импорта |

Каждый ID Value Object:
- Принимает `string $value` в конструкторе
- Валидирует формат UUID
- Предоставляет методы `value(): string` и `equals(self $other): bool`
- Имеет статический метод `generate(): self` для создания нового UUID

`ImportLogEntry`:
- Принимает `string $externalId`, `ImportLogEntryStatus $status`, `?string $errorReason` в конструкторе
- `$errorReason` обязателен при `status = Error`
- Предоставляет геттеры: `externalId(): string`, `status(): ImportLogEntryStatus`, `errorReason(): ?string`

`ImportSummary`:
- Принимает `int $imported`, `int $duplicates`, `int $errors`, `array $logEntries` в конструкторе
- `$logEntries` -- массив объектов `ImportLogEntry`
- Предоставляет геттеры и метод `toArray(): array`

### 3.3 Domain Entities (Aggregate Roots)

#### Ticket

- **Расположение:** `app/src/Timesheet/Domain/Entity/Ticket.php`
- **Атрибуты:** `id: TicketId`, `employeeId: string` (UUID), `taskId: string` (UUID), `workId: string` (UUID), `date: DateTimeImmutable`, `hours: string` (decimal), `comment: ?string`, `rateSnapshot: string` (decimal), `type: TicketType`, `importSource: ?string`, `externalId: ?string`, `isEditable: bool`
- **Фабричный метод `createManual(id, employeeId, taskId, workId, date, hours, comment, rateSnapshot)`:** создаёт Manual Ticket; валидирует `hours > 0`; устанавливает `type = Manual`, `isEditable = true`, `importSource = null`, `externalId = null`
- **Фабричный метод `createImported(id, employeeId, taskId, workId, date, hours, comment, rateSnapshot, importSource, externalId, allowEdit)`:** создаёт Imported Ticket; валидирует `hours > 0`; устанавливает `type = Imported`, `isEditable = allowEdit`; `importSource` и `externalId` обязательны
- **Фабричный метод `restore()`:** восстановление из хранилища без валидации
- **Метод `update(date, hours, workId, comment)`:** обновляет допустимые поля; валидирует `hours > 0`; бросает `TicketNotEditableException` если `isEditable = false`; `rateSnapshot` не изменяется; `taskId` не изменяется (осознанное ограничение ТЗ)
- **Инварианты:**
  - `hours` строго > 0
  - `rateSnapshot` неизменен после создания
  - Imported Ticket с `isEditable = false` не может быть обновлён
  - `importSource` и `externalId` обязательны для Imported Ticket
  - `importSource` и `externalId` всегда null для Manual Ticket

#### Import Policy

- **Расположение:** `app/src/Timesheet/Domain/Entity/ImportPolicy.php`
- **Атрибуты:** `id: ImportPolicyId`, `name: string`, `sourceSystem: string`, `mappingRules: array` (JSON), `allowEdit: bool`, `isActive: bool`
- **Фабричный метод `create(id, name, sourceSystem, mappingRules, allowEdit)`:** валидирует, что `name` и `sourceSystem` не пусты; устанавливает `isActive = false`
- **Фабричный метод `restore()`:** восстановление из хранилища без валидации
- **Метод `update(mappingRules, allowEdit, isActive)`:** обновляет допустимые поля
- **Метод `activate()`:** устанавливает `isActive = true`
- **Метод `deactivate()`:** устанавливает `isActive = false`
- **Инварианты:**
  - `name` не может быть пустым
  - `sourceSystem` не может быть пустым
  - Для одного `sourceSystem` одновременно может быть активна только одна Import Policy (проверяется на уровне Use Case)
  - Изменение Import Policy не пересчитывает ранее импортированные Ticket

---

## 4. Доменные репозитории

### Интерфейсы (Domain)

| Интерфейс | Расположение | Ключевые методы |
|---|---|---|
| `TicketRepositoryInterface` | `app/src/Timesheet/Domain/Repository/` | `save(Ticket)`, `findById(TicketId): ?Ticket`, `findAll(criteria): array`, `countAll(criteria): int`, `existsByImportSourceAndExternalId(string, string): bool` |
| `ImportPolicyRepositoryInterface` | `app/src/Timesheet/Domain/Repository/` | `save(ImportPolicy)`, `findById(ImportPolicyId): ?ImportPolicy`, `findAll(criteria): array`, `countAll(criteria): int`, `findActiveBySourceSystem(string): ?ImportPolicy` |

**Примечания:**
- `existsByImportSourceAndExternalId` -- проверка дубликатов при импорте по паре (`importSource` + `externalId`)
- `findActiveBySourceSystem` -- поиск активной политики для данного sourceSystem (для проверки инварианта ИН-18)
- `findAll` для Ticket поддерживает фильтрацию по `employeeId`, `projectId`, `crId`, `taskId`, `workId`, `dateFrom`, `dateTo` с пагинацией
- `findAll` для ImportPolicy возвращает все записи с пагинацией
- Все методы оперируют доменными типами (Value Objects, Domain Entity)

### Application Port-интерфейсы (межконтекстное взаимодействие)

| Интерфейс | Расположение | Описание |
|---|---|---|
| `TaskExistenceCheckerInterface` | `app/src/Timesheet/Application/Port/` | Проверка существования Task. Метод: `taskExists(string $taskId): bool` |
| `WorkExistenceCheckerInterface` | `app/src/Timesheet/Application/Port/` | Проверка существования Work. Метод: `workExists(string $workId): bool` |
| `RateProviderInterface` | `app/src/Timesheet/Application/Port/` | Получение актуального значения Rate. Метод: `getCurrentRate(string $employeeId, string $workId): string` (возвращает decimal-строку; `'0'` если Rate не найдена) |
| `CurrentUserProviderInterface` | `app/src/Timesheet/Application/Port/` | Получение текущего пользователя. Методы: `getCurrentUserId(): string`, `getCurrentUserRole(): string`, `isAdmin(): bool`, `isManager(): bool`, `isEmployee(): bool` |
| `ExternalDataFetcherInterface` | `app/src/Timesheet/Application/Port/` | Получение данных из внешней системы для импорта. Метод: `fetch(string $sourceSystem, array $mappingRules, DateTimeImmutable $dateFrom, DateTimeImmutable $dateTo): array` |
| `EmployeeResolverInterface` | `app/src/Timesheet/Application/Port/` | Разрешение внешнего идентификатора сотрудника во внутренний UUID. Метод: `resolve(string $value, string $matchBy): ?string` (`matchBy`: `email` или `externalId`; возвращает UUID или null) |
| `TaskResolverInterface` | `app/src/Timesheet/Application/Port/` | Разрешение внешнего идентификатора задачи во внутренний UUID. Метод: `resolve(string $value, string $matchBy): ?string` (`matchBy`: `name` или `externalId`) |
| `WorkResolverInterface` | `app/src/Timesheet/Application/Port/` | Разрешение внешнего идентификатора вида работ во внутренний UUID. Метод: `resolve(string $value, string $matchBy): ?string` (`matchBy`: `name` или `externalId`) |

**Реализации** размещаются в `app/src/Timesheet/Infrastructure/Port/` и выполняют прямые запросы к Persistence (таблицы `tasks`, `works`, `rates`, `users`), без межсервисных вызовов -- все данные в одной БД.

**Resolver-реализации** (`DoctrineEmployeeResolver`, `DoctrineTaskResolver`, `DoctrineWorkResolver`) выполняют поиск по заданному полю (`email`/`externalId`/`name`) и возвращают внутренний UUID. Используются только в `RunImportUseCase` при трансформации записей внешней системы.

`ExternalDataFetcherInterface` -- на первом этапе реализуется как `NullExternalDataFetcher`, возвращающий пустой массив. Реальные реализации добавляются по мере интеграции с конкретными внешними системами.

**Примечание по `RateProviderInterface`:** алгоритм определения актуальной Rate (приоритетная цепочка Work+Role > Work > Role > базовая, с учётом `effectiveFrom`) реализуется на стороне Infrastructure-реализации `DoctrineRateProvider`. Timesheet-домен не знает о деталях алгоритма; для него это чёрный ящик, возвращающий decimal-строку.

### Реализации (Infrastructure)

Размещаются в `app/src/Timesheet/Infrastructure/Repository/`, используют composition с Doctrine Repository из `Persistence`. Выполняют маппинг Domain Entity <-> Doctrine Entity через методы `toOrmEntity()` и `toDomainEntity()`.

---

## 5. Use Cases

### Command Use Cases (создание/обновление/импорт)

> **Примечание:** `RunImportUseCase` -- команда с побочным эффектом возврата результата. Использует `RunImportOutputPortInterface` для возврата сводки импорта, аналогично Query Use Cases.

| Use Case | Описание | InputDto | Зависимости |
|---|---|---|---|
| `CreateManualTicketUseCase` | Создание Manual Ticket (UC-TS-01) | `employeeId`, `taskId`, `workId`, `date`, `hours`, `?comment` | `TicketRepositoryInterface`, `TaskExistenceCheckerInterface`, `WorkExistenceCheckerInterface`, `RateProviderInterface`, `CurrentUserProviderInterface`, `EventDispatcherInterface` |
| `UpdateManualTicketUseCase` | Редактирование Manual Ticket (UC-TS-02) | `ticketId`, `?date`, `?hours`, `?workId`, `?comment` | `TicketRepositoryInterface`, `WorkExistenceCheckerInterface`, `CurrentUserProviderInterface`, `EventDispatcherInterface` |
| `CreateImportPolicyUseCase` | Создание Import Policy (UC-TS-04) | `name`, `sourceSystem`, `mappingRules`, `allowEdit` | `ImportPolicyRepositoryInterface`, `EventDispatcherInterface` |
| `UpdateImportPolicyUseCase` | Обновление Import Policy (UC-TS-06) | `importPolicyId`, `?mappingRules`, `?allowEdit`, `?isActive` | `ImportPolicyRepositoryInterface`, `EventDispatcherInterface` |
| `RunImportUseCase` | Запуск импорта Ticket (UC-TS-05) | `importPolicyId`, `dateFrom`, `dateTo` | `ImportPolicyRepositoryInterface`, `TicketRepositoryInterface`, `EmployeeResolverInterface`, `TaskResolverInterface`, `WorkResolverInterface`, `RateProviderInterface`, `ExternalDataFetcherInterface`, `RunImportOutputPortInterface`, `EventDispatcherInterface` |

### Query Use Cases (получение данных)

| Use Case | Описание | InputDto | OutputDto | Зависимости |
|---|---|---|---|---|
| `GetTicketUseCase` | Получение Ticket по ID | `ticketId` | Ticket (доменная сущность) | `TicketRepositoryInterface`, `CurrentUserProviderInterface` |
| `ListTicketsUseCase` | Просмотр Ticket List (UC-TS-03) | `?employeeId`, `?projectId`, `?crId`, `?taskId`, `?workId`, `?dateFrom`, `?dateTo`, `page`, `perPage` | `items[]`, `total`, `page`, `perPage` | `TicketRepositoryInterface`, `ListTicketsOutputPortInterface`, `CurrentUserProviderInterface` |
| `ListImportPoliciesUseCase` | Просмотр Import Policy List (UC-TS-06) | `page`, `perPage` | `items[]`, `total`, `page`, `perPage` | `ImportPolicyRepositoryInterface`, `ListImportPoliciesOutputPortInterface` |

### Детали Use Cases

**CreateManualTicketUseCase:**
1. Получение текущего пользователя через `CurrentUserProviderInterface`
2. Проверка прав: если не Admin -- `employeeId` обязан совпадать с ID текущего пользователя; при нарушении -- `TicketOwnershipViolationException`
3. Проверка существования Task через `TaskExistenceCheckerInterface::taskExists()`; при отсутствии -- выброс исключения
4. Проверка существования Work через `WorkExistenceCheckerInterface::workExists()`; при отсутствии -- выброс исключения
5. Получение актуальной Rate через `RateProviderInterface::getCurrentRate(employeeId, workId)` -- возвращает decimal-строку; при отсутствии Rate возвращает `'0'`
6. Вызов `Ticket::createManual(TicketId::generate(), employeeId, taskId, workId, date, hours, comment, rateSnapshot)`
7. Сохранение через `TicketRepositoryInterface::save()`
8. Публикация события `TicketsAdded`

**UpdateManualTicketUseCase:**
1. Поиск Ticket по ID; при отсутствии -- `TicketNotFoundException`
2. Проверка прав через `CurrentUserProviderInterface`: Employee может обновлять только собственный Ticket; при нарушении -- `TicketOwnershipViolationException`
3. Проверка `isEditable`: если `false` -- `TicketNotEditableException`
4. Если передан `workId` -- проверка существования Work через `WorkExistenceCheckerInterface`
5. Применение правил PUT-семантики: переданные поля обновляются; не переданные -- остаются без изменений
6. Вызов `Ticket::update(date, hours, workId, comment)` -- `rateSnapshot` не пересчитывается
7. Сохранение через `TicketRepositoryInterface::save()`
8. Публикация события `TicketUpdated`

**GetTicketUseCase:**
1. Поиск Ticket по ID через `TicketRepositoryInterface::findById()`; при отсутствии -- `TicketNotFoundException`
2. Проверка прав через `CurrentUserProviderInterface`: Employee может получить только собственный Ticket (НФТ-Б-03); Manager и Admin -- любой
3. Возврат доменной сущности Ticket (маппинг в Response DTO выполняется в контроллере/презентере)

**ListTicketsUseCase:**
1. Получение текущего пользователя и его роли через `CurrentUserProviderInterface`
2. Если роль Employee -- принудительная подстановка `employeeId` текущего пользователя (игнорирование переданного `employeeId`); Employee не может видеть чужие Ticket
3. Маппинг фильтров из InputDto
4. Запрос к `TicketRepositoryInterface` с фильтрацией и пагинацией
5. Маппинг доменных объектов в OutputDto
6. Передача через `$this->presenter->present(OutputDto)`

**Примечание к ListTicketsUseCase:** согласно НФТ-У-02, ответ содержит наименования связанных сущностей (Task name, Work name, Employee name), а не только их идентификаторы. Для получения наименований используются JOIN на уровне Persistence.

**CreateImportPolicyUseCase:**
1. Валидация `name` и `sourceSystem` -- не пустые
2. Вызов `ImportPolicy::create(ImportPolicyId::generate(), name, sourceSystem, mappingRules, allowEdit)`
3. Import Policy создаётся с `isActive = false` (активация -- отдельная операция через Update)
4. Сохранение через `ImportPolicyRepositoryInterface::save()`
5. Публикация события `ImportPolicyCreated`

**UpdateImportPolicyUseCase:**
1. Поиск Import Policy по ID; при отсутствии -- `ImportPolicyNotFoundException`
2. Если запрашивается активация (`isActive = true`):
   - Проверка через `ImportPolicyRepositoryInterface::findActiveBySourceSystem()`: для данного `sourceSystem` нет другой активной Import Policy (исключая текущую)
   - При конфликте -- `ImportPolicyConflictException`
3. Вызов `ImportPolicy::update(mappingRules, allowEdit, isActive)`
4. Сохранение через `ImportPolicyRepositoryInterface::save()`
5. Публикация события `ImportPolicyUpdated`

**ListImportPoliciesUseCase:**
1. Запрос к `ImportPolicyRepositoryInterface` с пагинацией
2. Маппинг доменных объектов в OutputDto
3. Передача через `$this->presenter->present(OutputDto)`

**RunImportUseCase:**
1. Поиск Import Policy по ID; при отсутствии -- `ImportPolicyNotFoundException`
2. Проверка: Import Policy активна (`isActive = true`); при нарушении -- `ImportPolicyNotActiveException`
3. Получение данных из внешней системы через `ExternalDataFetcherInterface::fetch(sourceSystem, mappingRules, dateFrom, dateTo)`
4. Для каждой записи:
   a. Проверка дубликата через `TicketRepositoryInterface::existsByImportSourceAndExternalId(importSource, externalId)` -- при дубликате: инкремент `duplicates`, добавление `ImportLogEntry` со статусом `Duplicate`, переход к следующей записи
   b. Сопоставление внешних идентификаторов `employee`, `task`, `work` с внутренними UUID через `EmployeeResolverInterface::resolve()`, `TaskResolverInterface::resolve()`, `WorkResolverInterface::resolve()` (используя `matchBy` из `mappingRules`)
   c. При ошибке сопоставления: инкремент `errors`, добавление `ImportLogEntry` со статусом `Error` с причиной; переход к следующей записи (импорт не прерывается)
   d. Получение `rateSnapshot` через `RateProviderInterface::getCurrentRate(employeeId, workId)`
   e. Вызов `Ticket::createImported(...)` с `importSource = importPolicy.sourceSystem`, `externalId` из записи, `isEditable = importPolicy.allowEdit`
   f. Сохранение через `TicketRepositoryInterface::save()`
   g. Инкремент `imported`, добавление `ImportLogEntry` со статусом `Imported`
5. Формирование `ImportSummary(imported, duplicates, errors, logEntries)`
6. Публикация события `TicketsImported(importPolicyId, imported, duplicates, errors)`
7. Передача результата через `$this->presenter->present(RunImportOutputDto)`

---

## 6. Доменные события и аудит-логирование

### 6.1 Доменные события

| Событие | Расположение | Поля | Триггер |
|---|---|---|---|
| `TicketsAdded` | `app/src/Timesheet/Domain/Event/` | `string $ticketId`, `string $employeeId`, `string $taskId`, `string $workId`, `string $date`, `string $hours`, `string $rateSnapshot` | `CreateManualTicketUseCase` |
| `TicketUpdated` | `app/src/Timesheet/Domain/Event/` | `string $ticketId` | `UpdateManualTicketUseCase` |
| `TicketsImported` | `app/src/Timesheet/Domain/Event/` | `string $importPolicyId`, `int $imported`, `int $duplicates`, `int $errors` | `RunImportUseCase` |
| `ImportPolicyCreated` | `app/src/Timesheet/Domain/Event/` | `string $importPolicyId`, `string $name`, `string $sourceSystem` | `CreateImportPolicyUseCase` |
| `ImportPolicyUpdated` | `app/src/Timesheet/Domain/Event/` | `string $importPolicyId` | `UpdateImportPolicyUseCase` |

Все события -- `final readonly class`, содержат `DateTimeImmutable $occurredAt`.

### 6.2 Аудит-логирование (НФТ-Б-06)

**Механизм:** Event Listener на доменные события, запись через Symfony Logger (Monolog).

**Расположение:** `app/src/Timesheet/Infrastructure/EventListener/AuditLogEventListener.php`

**Принцип работы:**
1. При публикации доменного события Event Listener перехватывает событие
2. Формирует структурированную запись аудит-лога
3. Записывает через `LoggerInterface` (Monolog)

**Состав записи аудит-лога:**
- Идентификатор пользователя, выполнившего операцию
- Тип действия: `create`, `update`, `import`
- Тип сущности и её идентификатор
- Временная метка (UTC)
- Результат операции (`success` / `failure`)
- Для импорта: сводка (imported, duplicates, errors)

**Место хранения:** стандартный лог-файл Symfony (stdout). Отдельная таблица `audit_log` не создаётся.

---

## 7. Доменные исключения

| Исключение | Когда выбрасывается |
|---|---|
| `TicketNotFoundException` | Ticket не найден по ID |
| `TicketNotEditableException` | Попытка редактировать Ticket с `isEditable = false` |
| `TicketOwnershipViolationException` | Employee пытается создать/редактировать/просмотреть Ticket другого сотрудника |
| `InvalidTicketHoursException` | `hours <= 0` при создании или обновлении Ticket |
| `ImportPolicyNotFoundException` | Import Policy не найдена по ID |
| `ImportPolicyNotActiveException` | Попытка запустить импорт для неактивной Import Policy |
| `ImportPolicyConflictException` | Попытка активировать Import Policy при наличии другой активной для того же `sourceSystem` |

Все исключения наследуют `\DomainException`.

---

## 8. Persistence (Doctrine)

### Doctrine Entities

#### `Persistence\Entity\Ticket`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `employee_id` | `guid` | NOT NULL, INDEX |
| `task_id` | `guid` | NOT NULL, INDEX |
| `work_id` | `guid` | NOT NULL, INDEX |
| `date` | `date_immutable` | NOT NULL, INDEX |
| `hours` | `decimal(8,2)` | NOT NULL |
| `comment` | `text` | NULLABLE |
| `rate_snapshot` | `decimal(12,2)` | NOT NULL |
| `type` | `string(20)` | NOT NULL (manual/imported) |
| `import_source` | `string(255)` | NULLABLE |
| `external_id` | `string(255)` | NULLABLE |
| `is_editable` | `boolean` | NOT NULL, DEFAULT true |
| `created_at` | `datetime_immutable` | NOT NULL |
| `updated_at` | `datetime_immutable` | NULLABLE |

**Индексы:**
- На `employee_id` (фильтрация по сотруднику)
- На `task_id` (фильтрация по задаче, проверка существования Ticket для Task)
- На `work_id` (фильтрация по виду работ, проверка существования Ticket для Work)
- На `date` (фильтрация по периоду)
- Composite UNIQUE на `(import_source, external_id)` WHERE `import_source IS NOT NULL` -- для идемпотентности импорта (ИН-20)

#### `Persistence\Entity\ImportPolicy`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `name` | `string(255)` | NOT NULL |
| `source_system` | `string(255)` | NOT NULL, INDEX |
| `mapping_rules` | `json` | NOT NULL |
| `allow_edit` | `boolean` | NOT NULL, DEFAULT false |
| `is_active` | `boolean` | NOT NULL, DEFAULT false |
| `created_at` | `datetime_immutable` | NOT NULL |
| `updated_at` | `datetime_immutable` | NULLABLE |

**Индексы:**
- На `source_system` (поиск активной политики по sourceSystem)
- Partial unique на `(source_system)` WHERE `is_active = true` -- для гарантии инварианта ИН-18 на уровне БД

### Doctrine Repositories

Каждый наследует `ServiceEntityRepository`, содержит:
- `save(Entity, flush)`, `remove(Entity, flush)`
- Методы поиска с фильтрацией

**TicketRepository:**
- `findActiveAll(criteria): array` -- фильтрация по `employeeId`, `taskId`, `workId`, `dateFrom`, `dateTo`, с пагинацией
- `countActive(criteria): int`
- `existsByImportSourceAndExternalId(string, string): bool` -- проверка дубликатов
- `findByEmployeeId(string): array`
- `findByTaskId(string): array`
- Поддержка JOIN к таблицам `tasks`, `works`, `users` для получения наименований (НФТ-У-02)
- Фильтрация по `projectId`/`crId` реализуется через JOIN к таблице `tasks` (и далее к `projects`/`change_requests`) на уровне Persistence -- архитектурный компромисс для монолита
- Методы с пагинацией (QueryBuilder + `setFirstResult()`/`setMaxResults()`)
- НФТ-П-05: не загружает более 1000 записей за запрос

**ImportPolicyRepository:**
- `findAll(criteria): array` -- все записи с пагинацией
- `countAll(criteria): int`
- `findActiveBySourceSystem(string): ?ImportPolicy`

---

## 9. Infrastructure (HTTP-слой)

### API-маршруты

| Метод | Путь | Контроллер | Имя маршрута | Права |
|---|---|---|---|---|
| POST | `/timesheet/tickets` | `CreateManualTicketController` | `timesheet_create_ticket` | Employee (за себя), Admin (за любого) |
| GET | `/timesheet/tickets/{id}` | `GetTicketController` | `timesheet_get_ticket` | Employee (свой), Manager, Admin |
| PUT | `/timesheet/tickets/{id}` | `UpdateManualTicketController` | `timesheet_update_ticket` | Employee (свой), Admin |
| GET | `/timesheet/tickets` | `ListTicketsController` | `timesheet_list_tickets` | Employee (только свои), Manager, Admin |
| POST | `/timesheet/import-policies` | `CreateImportPolicyController` | `timesheet_create_import_policy` | Admin |
| PUT | `/timesheet/import-policies/{id}` | `UpdateImportPolicyController` | `timesheet_update_import_policy` | Admin |
| GET | `/timesheet/import-policies` | `ListImportPoliciesController` | `timesheet_list_import_policies` | Admin |
| POST | `/timesheet/import-policies/{id}/run` | `RunImportController` | `timesheet_run_import` | Admin |

### Request DTO с валидацией

**CreateManualTicketRequestDto:**
- `employeeId: string` -- `#[NotBlank]`, `#[Uuid]`
- `taskId: string` -- `#[NotBlank]`, `#[Uuid]`
- `workId: string` -- `#[NotBlank]`, `#[Uuid]`
- `date: string` -- `#[NotBlank]`, `#[Date]`
- `hours: string` -- `#[NotBlank]`, `#[Positive]`
- `comment: ?string` -- `#[Length(max: 1000)]`

**UpdateManualTicketRequestDto:**
- `date: ?string` -- `#[Date]`
- `hours: ?string` -- `#[Positive]`
- `workId: ?string` -- `#[Uuid]`
- `comment: ?string` -- `#[Length(max: 1000)]`

**ListTicketsRequestDto** (GET, query-параметры):
- `employeeId: ?string` -- `#[Uuid]`
- `projectId: ?string` -- `#[Uuid]`
- `crId: ?string` -- `#[Uuid]`
- `taskId: ?string` -- `#[Uuid]`
- `workId: ?string` -- `#[Uuid]`
- `dateFrom: ?string` -- `#[Date]`
- `dateTo: ?string` -- `#[Date]`
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

**CreateImportPolicyRequestDto:**
- `name: string` -- `#[NotBlank]`, `#[Length(max: 255)]`
- `sourceSystem: string` -- `#[NotBlank]`, `#[Length(max: 255)]`
- `mappingRules: array` -- `#[NotBlank]`
- `allowEdit: bool` -- `#[NotNull]`

**UpdateImportPolicyRequestDto:**
- `mappingRules: ?array`
- `allowEdit: ?bool`
- `isActive: ?bool`

**ListImportPoliciesRequestDto** (GET, query-параметры):
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

**RunImportRequestDto:**
- `dateFrom: string` -- `#[NotBlank]`, `#[Date]`
- `dateTo: string` -- `#[NotBlank]`, `#[Date]`

### Response DTO

Каждый Response DTO содержит OpenAPI-атрибуты (`#[OA\Schema]`, `#[OA\Property]`).

**TicketResponseDto:** `id`, `employeeId`, `employeeName`, `taskId`, `taskName`, `workId`, `workName`, `date`, `hours`, `comment`, `rateSnapshot`, `type`, `importSource`, `externalId`, `isEditable`

**ImportPolicyResponseDto:** `id`, `name`, `sourceSystem`, `mappingRules`, `allowEdit`, `isActive`

**ImportSummaryResponseDto:** `imported`, `duplicates`, `errors`

List Response DTO содержат `items[]`, `total`, `page`, `perPage`.

**Важно:** согласно НФТ-У-02, Ticket List возвращает наименования связанных сущностей (`employeeName`, `taskName`, `workName`), а не только их идентификаторы.

---

## 10. Зависимости между компонентами

### Граф зависимостей (направление: зависимый -> от чего зависит)

```
Domain Layer (нет внешних зависимостей):
  Entity -> ValueObject, Enum, Exception
  Repository Interface -> Entity, ValueObject

Application Layer (зависит от Domain):
  UseCase -> Repository Interface, Entity, ValueObject, Enum, Exception, Port Interface, EventDispatcherInterface
  InputDto -> (только примитивы, нет зависимостей)
  OutputDto -> (только примитивы, нет зависимостей)
  OutputPort -> OutputDto
  Port Interface -> (только примитивы)

Infrastructure Layer (зависит от Domain + Application):
  Controller -> UseCase, Transformer, Presenter, RequestDto
  Transformer -> RequestDto, InputDto
  Presenter -> OutputDto, OutputPort, ResponseDto
  ValueResolver -> AbstractJsonValueResolver/AbstractValueResolver, RequestDto
  DoctrineRepository -> Domain Repository Interface, Domain Entity, Domain VO, Persistence Entity, Persistence Repository
  Port Implementation (ExistenceChecker, Resolver, RateProvider) -> Persistence Repository
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

- **Timesheet -> ProjectManagement:** проверка существования Task при создании Ticket (через `TaskExistenceCheckerInterface`); разрешение внешних идентификаторов Task при импорте (через `TaskResolverInterface`) -> прямые запросы к Persistence
- **Timesheet -> WorkCatalog:** проверка существования Work и получение актуальной Rate при создании Ticket (через `WorkExistenceCheckerInterface`, `RateProviderInterface`); разрешение внешних идентификаторов Work при импорте (через `WorkResolverInterface`) -> прямые запросы к Persistence
- **Timesheet -> Identity:** идентификация текущего пользователя и проверка прав (через `CurrentUserProviderInterface` -> Symfony Security); разрешение внешних идентификаторов Employee при импорте (через `EmployeeResolverInterface` -> прямой запрос к Persistence)
- **Reporting -> Timesheet:** Reporting запрашивает Ticket List (через API или прямой запрос к Persistence -- определяется при реализации Reporting)
- **ProjectManagement -> Timesheet (обратная):** при soft-delete Task проверка наличия привязанных Ticket (реализуется на стороне ProjectManagement через Port-интерфейс)
- **WorkCatalog -> Timesheet (обратная):** при soft-delete Work проверка наличия привязанных Ticket (реализуется на стороне WorkCatalog через Port-интерфейс `TicketExistenceByWorkCheckerInterface`)
- **Identity -> Timesheet (обратная):** при soft-delete User проверка наличия привязанных Ticket (реализуется на стороне Identity через Port-интерфейс `TicketExistenceCheckerInterface`)

---

## 11. Порядок реализации

### Этап 1: Domain Layer

**Шаг 1.1:** Enum
- `TicketType`
- `ImportLogEntryStatus`

**Шаг 1.2:** Value Objects
- `TicketId`, `ImportPolicyId`, `ImportLogEntry`, `ImportSummary`

**Шаг 1.3:** Доменные исключения
- Все исключения из раздела 7

**Шаг 1.4:** Доменные события
- `TicketsAdded`, `TicketUpdated`, `TicketsImported`, `ImportPolicyCreated`, `ImportPolicyUpdated`

**Шаг 1.5:** Domain Entities
- `Ticket` (фабричные методы `createManual()`, `createImported()`, `restore()`, метод `update()`; поле `externalId`)
- `ImportPolicy` (фабричные методы `create()`, `restore()`, методы `update()`, `activate()`, `deactivate()`)

**Шаг 1.6:** Интерфейсы репозиториев
- `TicketRepositoryInterface`
- `ImportPolicyRepositoryInterface`

### Этап 2: Persistence Layer

**Шаг 2.1:** Doctrine Entities
- `Persistence\Entity\Ticket` (включая колонку `external_id`), `ImportPolicy`

**Шаг 2.2:** Doctrine Repositories
- `Persistence\Repository\TicketRepository`, `ImportPolicyRepository`

**Шаг 2.3:** Миграции
- Генерация через `doctrine:migrations:diff`

### Этап 3: Application Layer

**Шаг 3.1:** Port-интерфейсы (межконтекстные проверки и разрешение идентификаторов)
- `TaskExistenceCheckerInterface`, `WorkExistenceCheckerInterface`, `RateProviderInterface`, `CurrentUserProviderInterface`, `ExternalDataFetcherInterface`
- `EmployeeResolverInterface`, `TaskResolverInterface`, `WorkResolverInterface`

**Шаг 3.2:** InputDto и OutputDto
- Все DTO из раздела 5, включая `GetTicketInputDto`

**Шаг 3.3:** OutputPort-интерфейсы
- `ListTicketsOutputPortInterface`, `ListImportPoliciesOutputPortInterface`, `RunImportOutputPortInterface`

**Шаг 3.4:** Command Use Cases -- Ticket
- `CreateManualTicketUseCase`, `UpdateManualTicketUseCase`

**Шаг 3.5:** Query Use Case -- Ticket
- `GetTicketUseCase`

**Шаг 3.6:** Command Use Cases -- Import Policy
- `CreateImportPolicyUseCase`, `UpdateImportPolicyUseCase`

**Шаг 3.7:** Command Use Case -- Import
- `RunImportUseCase`

**Шаг 3.8:** Query Use Cases -- списки
- `ListTicketsUseCase`, `ListImportPoliciesUseCase`

### Этап 4: Infrastructure Layer

**Шаг 4.1:** Реализации доменных репозиториев
- `TicketRepository`, `ImportPolicyRepository`

**Шаг 4.2:** Реализации Port-интерфейсов
- `DoctrineTaskExistenceChecker`, `DoctrineWorkExistenceChecker`, `DoctrineRateProvider`, `SecurityCurrentUserProvider`, `NullExternalDataFetcher`
- `DoctrineEmployeeResolver`, `DoctrineTaskResolver`, `DoctrineWorkResolver`

**Шаг 4.3:** Request DTO
- Все Request DTO с атрибутами валидации

**Шаг 4.4:** Response DTO
- Все Response DTO с OpenAPI-атрибутами

**Шаг 4.5:** Value Resolvers
- По одному на каждый Request DTO

**Шаг 4.6:** Input Transformers
- По одному на каждый Use Case, включая `GetTicketInputTransformer`

**Шаг 4.7:** Presenters
- `HttpListTicketsPresenter`, `HttpListImportPoliciesPresenter`, `HttpRunImportPresenter`

**Шаг 4.8:** Controllers
- Все 8 контроллеров (включая `GetTicketController`)

**Шаг 4.9:** Event Listener для аудит-логирования
- `AuditLogEventListener` -- подписка на все доменные события, запись через Monolog

**Шаг 4.10:** DI-конфигурация
- Alias-привязки интерфейсов к реализациям в `config/services.php`
- Регистрация Event Listener

### Этап 5: Тестирование

**Шаг 5.1:** Unit-тесты Domain (Entity, VO, Enum)
**Шаг 5.2:** Unit-тесты Application (Use Cases)
**Шаг 5.3:** Unit-тесты Infrastructure (Transformers, Presenters, Repositories)
**Шаг 5.4:** Интеграционные тесты (Doctrine Repositories)
**Шаг 5.5:** Функциональные тесты (Controllers через WebTestCase)

---

## 12. Тестирование

### Unit-тесты Domain

| Тест | Что проверяется |
|---|---|
| `TicketTest` | `createManual()`: создание с валидными данными, установка `type = Manual`, `isEditable = true`, `externalId = null`; `createImported()`: установка `type = Imported`, `isEditable` по значению `allowEdit`, `externalId` заполнен; `update()`: обновление допустимых полей, неизменность `rateSnapshot`, выброс `TicketNotEditableException` при `isEditable = false`; `restore()`; валидация `hours > 0` |
| `ImportPolicyTest` | `create()`: создание с валидными данными, `isActive = false` по умолчанию; `update()`: обновление `mappingRules`, `allowEdit`, `isActive`; `activate()`/`deactivate()`; `restore()`; валидация пустого `name`/`sourceSystem` |
| `TicketTypeTest` | Значения enum; `getLabel()` |
| `ImportLogEntryStatusTest` | Значения enum; `getLabel()` |
| `ImportLogEntryTest` | Создание с разными статусами; обязательность `errorReason` при `status = Error` |
| `ImportSummaryTest` | Подсчёт итогов; `toArray()` |
| ID Value Objects | Валидация UUID; `equals()`; `generate()` |

### Unit-тесты Application

| Тест | Что проверяется |
|---|---|
| `CreateManualTicketUseCaseTest` | Вызов `save()` с доменным объектом; маппинг InputDto -> VO; проверка прав (Employee за себя -- ОК, Employee за другого -- `TicketOwnershipViolationException`; Admin за любого -- ОК); проверка существования Task; получение `rateSnapshot`; публикация `TicketsAdded` |
| `UpdateManualTicketUseCaseTest` | Успешное обновление; выброс `TicketNotFoundException`; выброс `TicketOwnershipViolationException`; выброс `TicketNotEditableException`; неизменность `rateSnapshot`; публикация `TicketUpdated` |
| `GetTicketUseCaseTest` | Успешное получение; Employee видит только свой Ticket; Manager/Admin видят любой; выброс `TicketNotFoundException`; выброс `TicketOwnershipViolationException` для Employee при запросе чужого Ticket |
| `ListTicketsUseCaseTest` | Фильтрация Employee (принудительная подстановка `employeeId`); Manager/Admin -- произвольные фильтры; вызов `present()` с корректным OutputDto; пагинация; пустой результат |
| `CreateImportPolicyUseCaseTest` | Создание с валидными данными; `isActive = false` по умолчанию; публикация `ImportPolicyCreated` |
| `UpdateImportPolicyUseCaseTest` | Успешное обновление; активация при отсутствии конфликта; выброс `ImportPolicyConflictException`; выброс `ImportPolicyNotFoundException`; публикация `ImportPolicyUpdated` |
| `RunImportUseCaseTest` | Полный сценарий с imported/duplicates/errors; идемпотентность (повторный запуск -- все duplicates); неактивная политика -- `ImportPolicyNotActiveException`; публикация `TicketsImported`; разрешение employee/task/work через Resolver-ы; ошибка сопоставления -- запись в logEntries со статусом `Error` |
| `ListImportPoliciesUseCaseTest` | Пагинация; вызов `present()` |

### Unit-тесты Infrastructure

| Тест | Что проверяется |
|---|---|
| `TicketRepositoryTest` | Маппинг Domain Entity <-> Doctrine Entity (включая `externalId`); делегирование save/find; `existsByImportSourceAndExternalId` |
| `ImportPolicyRepositoryTest` | Маппинг; делегирование; `findActiveBySourceSystem` |
| Transformer-тесты | Маппинг Request DTO -> InputDto для каждого Transformer |
| Presenter-тесты | Маппинг OutputDto -> Response DTO для каждого Presenter |
| `AuditLogEventListenerTest` | Перехват всех доменных событий; формирование корректной записи лога |

### Интеграционные тесты

| Тест | Что проверяется |
|---|---|
| `TicketRepositoryTest` | CRUD-операции; фильтрация по `employee_id`, `task_id`, `work_id`, `date`; пагинация; проверка дубликатов импорта по `(import_source, external_id)`; JOIN для наименований; фильтрация по `projectId`/`crId` через JOIN |
| `ImportPolicyRepositoryTest` | CRUD-операции; `findActiveBySourceSystem`; partial unique index |

### Функциональные тесты

| Тест | Что проверяется |
|---|---|
| `CreateManualTicketControllerTest` | HTTP 201; валидация полей; Employee за себя -- ОК; Employee за другого -- HTTP 403; Admin за любого -- ОК; несуществующая Task -- HTTP 404/422 |
| `GetTicketControllerTest` | HTTP 200; Ticket не найден -- HTTP 404; Employee запрашивает чужой -- HTTP 403; Manager/Admin -- ОК |
| `UpdateManualTicketControllerTest` | HTTP 200; Ticket не найден -- HTTP 404; чужой Ticket -- HTTP 403; `isEditable = false` -- HTTP 409; неизменность `rateSnapshot` |
| `ListTicketsControllerTest` | HTTP 200; Employee видит только свои; Manager/Admin видят все; фильтрация; пагинация; пустой список; наименования в ответе |
| `CreateImportPolicyControllerTest` | HTTP 201; валидация; только Admin |
| `UpdateImportPolicyControllerTest` | HTTP 200; конфликт активации -- HTTP 409; не найдена -- HTTP 404 |
| `ListImportPoliciesControllerTest` | HTTP 200; пагинация; только Admin |
| `RunImportControllerTest` | HTTP 200 со сводкой; неактивная политика -- HTTP 409; не найдена -- HTTP 404; идемпотентность |
