# План реализации домена Project Management

**Версия:** 1.3
**Дата:** 2026-03-22
**Основание:** docs/technical-specification-parts/project-management.md (v1.3)

---

## Содержание

1. [Обзор архитектуры](#1-обзор-архитектуры)
2. [Структура модулей и файлов](#2-структура-модулей-и-файлов)
3. [Сущности, Value Objects, Enum](#3-сущности-value-objects-enum)
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

Домен Project Management реализуется как отдельный Bounded Context `ProjectManagement` в рамках существующей чистой архитектуры проекта. Структура следует принципам Clean Architecture и DDD:

- **Domain** -- сущности, Value Objects, Enum, интерфейсы репозиториев, доменные события, исключения
- **Application** -- Use Cases, InputDto, OutputDto, OutputPort-интерфейсы
- **Infrastructure** -- контроллеры, DTO запросов/ответов, Value Resolvers, Input Transformers, Presenters, реализации репозиториев, Event Listener для аудит-логирования

Persistence-слой (Doctrine Entity и Repository) размещается в домене `Persistence` -- архитектурный компромисс проекта.

---

## 2. Структура модулей и файлов

### Bounded Context: ProjectManagement

```
app/src/
├── ProjectManagement/
│   ├── Domain/
│   │   ├── Entity/
│   │   │   ├── Client.php
│   │   │   ├── Project.php
│   │   │   ├── ChangeRequest.php
│   │   │   └── Task.php
│   │   ├── ValueObject/
│   │   │   ├── ClientId.php
│   │   │   ├── ProjectId.php
│   │   │   ├── ChangeRequestId.php
│   │   │   ├── TaskId.php
│   │   │   └── Estimate.php
│   │   ├── Enum/
│   │   │   └── ProjectStatus.php
│   │   ├── Repository/
│   │   │   ├── ClientRepositoryInterface.php
│   │   │   ├── ProjectRepositoryInterface.php
│   │   │   ├── ChangeRequestRepositoryInterface.php
│   │   │   └── TaskRepositoryInterface.php
│   │   ├── Event/
│   │   │   ├── ClientCreated.php
│   │   │   ├── ClientUpdated.php
│   │   │   ├── ClientDeleted.php
│   │   │   ├── ProjectCreated.php
│   │   │   ├── ProjectUpdated.php
│   │   │   ├── ProjectDeleted.php
│   │   │   ├── ChangeRequestCreated.php
│   │   │   ├── ChangeRequestUpdated.php
│   │   │   ├── ChangeRequestDeleted.php
│   │   │   ├── TaskCreated.php
│   │   │   ├── TaskUpdated.php
│   │   │   └── TaskDeleted.php
│   │   └── Exception/
│   │       ├── ClientNotFoundException.php
│   │       ├── ProjectNotFoundException.php
│   │       ├── ChangeRequestNotFoundException.php
│   │       ├── TaskNotFoundException.php
│   │       ├── ClientHasActiveProjectsException.php
│   │       ├── ProjectHasLinkedEntitiesException.php
│   │       ├── ChangeRequestHasLinkedTasksException.php
│   │       ├── TaskHasLinkedTicketsException.php
│   │       ├── InvalidTaskParentException.php
│   │       └── EntityDeletedException.php
│   ├── Application/
│   │   ├── UseCase/
│   │   │   ├── CreateClientUseCase.php
│   │   │   ├── UpdateClientUseCase.php
│   │   │   ├── DeleteClientUseCase.php
│   │   │   ├── ListClientsUseCase.php
│   │   │   ├── CreateProjectUseCase.php
│   │   │   ├── UpdateProjectUseCase.php
│   │   │   ├── DeleteProjectUseCase.php
│   │   │   ├── ListProjectsUseCase.php
│   │   │   ├── CreateChangeRequestUseCase.php
│   │   │   ├── UpdateChangeRequestUseCase.php
│   │   │   ├── DeleteChangeRequestUseCase.php
│   │   │   ├── ListChangeRequestsUseCase.php
│   │   │   ├── CreateTaskUseCase.php
│   │   │   ├── UpdateTaskUseCase.php
│   │   │   ├── DeleteTaskUseCase.php
│   │   │   └── ListTasksUseCase.php
│   │   ├── Dto/
│   │   │   ├── CreateClientInputDto.php
│   │   │   ├── UpdateClientInputDto.php
│   │   │   ├── DeleteClientInputDto.php
│   │   │   ├── ListClientsInputDto.php
│   │   │   ├── ListClientsOutputDto.php
│   │   │   ├── CreateProjectInputDto.php
│   │   │   ├── UpdateProjectInputDto.php
│   │   │   ├── DeleteProjectInputDto.php
│   │   │   ├── ListProjectsInputDto.php
│   │   │   ├── ListProjectsOutputDto.php
│   │   │   ├── CreateChangeRequestInputDto.php
│   │   │   ├── UpdateChangeRequestInputDto.php
│   │   │   ├── DeleteChangeRequestInputDto.php
│   │   │   ├── ListChangeRequestsInputDto.php
│   │   │   ├── ListChangeRequestsOutputDto.php
│   │   │   ├── CreateTaskInputDto.php
│   │   │   ├── UpdateTaskInputDto.php
│   │   │   ├── DeleteTaskInputDto.php
│   │   │   ├── ListTasksInputDto.php
│   │   │   └── ListTasksOutputDto.php
│   │   └── Port/
│   │       ├── ListClientsOutputPortInterface.php
│   │       ├── ListProjectsOutputPortInterface.php
│   │       ├── ListChangeRequestsOutputPortInterface.php
│   │       ├── ListTasksOutputPortInterface.php
│   │       └── TicketExistenceCheckerInterface.php
│   └── Infrastructure/
│       ├── Controller/
│       │   ├── CreateClientController.php
│       │   ├── UpdateClientController.php
│       │   ├── DeleteClientController.php
│       │   ├── ListClientsController.php
│       │   ├── CreateProjectController.php
│       │   ├── UpdateProjectController.php
│       │   ├── DeleteProjectController.php
│       │   ├── ListProjectsController.php
│       │   ├── CreateChangeRequestController.php
│       │   ├── UpdateChangeRequestController.php
│       │   ├── DeleteChangeRequestController.php
│       │   ├── ListChangeRequestsController.php
│       │   ├── CreateTaskController.php
│       │   ├── UpdateTaskController.php
│       │   ├── DeleteTaskController.php
│       │   └── ListTasksController.php
│       ├── Dto/
│       │   ├── CreateClientRequestDto.php
│       │   ├── UpdateClientRequestDto.php
│       │   ├── CreateProjectRequestDto.php
│       │   ├── UpdateProjectRequestDto.php
│       │   ├── CreateChangeRequestRequestDto.php
│       │   ├── UpdateChangeRequestRequestDto.php
│       │   ├── CreateTaskRequestDto.php
│       │   ├── UpdateTaskRequestDto.php
│       │   ├── ListClientsRequestDto.php
│       │   ├── ListProjectsRequestDto.php
│       │   ├── ListChangeRequestsRequestDto.php
│       │   ├── ListTasksRequestDto.php
│       │   ├── ClientResponseDto.php
│       │   ├── ProjectResponseDto.php
│       │   ├── ChangeRequestResponseDto.php
│       │   ├── TaskResponseDto.php
│       │   ├── ClientListResponseDto.php
│       │   ├── ProjectListResponseDto.php
│       │   ├── ChangeRequestListResponseDto.php
│       │   └── TaskListResponseDto.php
│       ├── ValueResolver/
│       │   ├── CreateClientValueResolver.php
│       │   ├── UpdateClientValueResolver.php
│       │   ├── CreateProjectValueResolver.php
│       │   ├── UpdateProjectValueResolver.php
│       │   ├── CreateChangeRequestValueResolver.php
│       │   ├── UpdateChangeRequestValueResolver.php
│       │   ├── CreateTaskValueResolver.php
│       │   ├── UpdateTaskValueResolver.php
│       │   ├── ListClientsValueResolver.php
│       │   ├── ListProjectsValueResolver.php
│       │   ├── ListChangeRequestsValueResolver.php
│       │   └── ListTasksValueResolver.php
│       ├── Transformer/
│       │   ├── CreateClientInputTransformer.php
│       │   ├── UpdateClientInputTransformer.php
│       │   ├── DeleteClientInputTransformer.php
│       │   ├── ListClientsInputTransformer.php
│       │   ├── CreateProjectInputTransformer.php
│       │   ├── UpdateProjectInputTransformer.php
│       │   ├── DeleteProjectInputTransformer.php
│       │   ├── ListProjectsInputTransformer.php
│       │   ├── CreateChangeRequestInputTransformer.php
│       │   ├── UpdateChangeRequestInputTransformer.php
│       │   ├── DeleteChangeRequestInputTransformer.php
│       │   ├── ListChangeRequestsInputTransformer.php
│       │   ├── CreateTaskInputTransformer.php
│       │   ├── UpdateTaskInputTransformer.php
│       │   ├── DeleteTaskInputTransformer.php
│       │   └── ListTasksInputTransformer.php
│       ├── Presenter/
│       │   ├── HttpListClientsPresenter.php
│       │   ├── HttpListProjectsPresenter.php
│       │   ├── HttpListChangeRequestsPresenter.php
│       │   └── HttpListTasksPresenter.php
│       ├── EventListener/
│       │   └── AuditLogEventListener.php
│       └── Repository/
│           ├── ClientRepository.php
│           ├── ProjectRepository.php
│           ├── ChangeRequestRepository.php
│           └── TaskRepository.php
```

### Persistence

```
app/src/
├── Persistence/
│   ├── Entity/
│   │   ├── Client.php
│   │   ├── Project.php
│   │   ├── ChangeRequest.php
│   │   └── Task.php
│   └── Repository/
│       ├── ClientRepository.php
│       ├── ProjectRepository.php
│       ├── ChangeRequestRepository.php
│       └── TaskRepository.php
```

---

## 3. Сущности, Value Objects, Enum

### 3.1 Enum

#### ProjectStatus

- **Расположение:** `app/src/ProjectManagement/Domain/Enum/ProjectStatus.php`
- **Тип:** `enum ProjectStatus: string`
- **Кейсы:** `Active = 'active'`, `Closed = 'closed'`
- **Методы:** `getLabel(): string` (русскоязычные метки: "Активный", "Закрыт")

### 3.2 Value Objects

| Value Object | Расположение | Описание |
|---|---|---|
| `ClientId` | `app/src/ProjectManagement/Domain/ValueObject/ClientId.php` | Типизированный UUID-идентификатор клиента |
| `ProjectId` | `app/src/ProjectManagement/Domain/ValueObject/ProjectId.php` | Типизированный UUID-идентификатор проекта |
| `ChangeRequestId` | `app/src/ProjectManagement/Domain/ValueObject/ChangeRequestId.php` | Типизированный UUID-идентификатор CR |
| `TaskId` | `app/src/ProjectManagement/Domain/ValueObject/TaskId.php` | Типизированный UUID-идентификатор задачи |
| `Estimate` | `app/src/ProjectManagement/Domain/ValueObject/Estimate.php` | Эстимейт трудозатрат в часах (положительное decimal) |

Каждый ID Value Object:
- Принимает `string $value` в конструкторе
- Валидирует формат UUID
- Предоставляет методы `value(): string` и `equals(self $other): bool`
- Имеет статический метод `generate(): self` для создания нового UUID

`Estimate`:
- Принимает `float $value` в конструкторе
- Валидирует: значение должно быть строго положительным (`> 0`)
- Предоставляет метод `value(): float`
- Предоставляет метод `equals(self $other): bool`

### 3.3 Domain Entities (Aggregate Roots)

#### Client

- **Расположение:** `app/src/ProjectManagement/Domain/Entity/Client.php`
- **Атрибуты:** `id: ClientId`, `name: string`, `description: ?string`, `deletedAt: ?DateTimeImmutable`
- **Фабричный метод `create()`:** валидирует, что `name` не пустой; устанавливает `deletedAt = null`
- **Фабричный метод `restore()`:** восстановление из хранилища без валидации
- **Метод `update(name, description)`:** обновление изменяемых полей; валидирует, что `name` не пустой
- **Метод `softDelete()`:** устанавливает `deletedAt` в текущее время
- **Метод `isDeleted(): bool`:** проверяет `deletedAt !== null`
- **Инварианты:** `name` не может быть пустым; Client не может быть помечен удалённым при наличии активных Project

#### Project

- **Расположение:** `app/src/ProjectManagement/Domain/Entity/Project.php`
- **Атрибуты:** `id: ProjectId`, `clientId: ClientId`, `name: string`, `status: ProjectStatus`, `description: ?string`, `deletedAt: ?DateTimeImmutable`
- **Фабричный метод `create()`:** валидирует, что `name` не пустой; устанавливает `deletedAt = null`; Admin может указать любой допустимый статус (в том числе `Closed`, например для импорта архивных данных)
- **Фабричный метод `restore()`:** восстановление из хранилища
- **Метод `update(name, status, description)`:** обновление изменяемых полей; валидирует `name` и `status`
- **Метод `softDelete()`:** устанавливает `deletedAt` в текущее время
- **Метод `isDeleted(): bool`:** проверяет `deletedAt !== null`
- **Методы:** `isActive(): bool`
- **Инварианты:** `name` не может быть пустым; `status` -- валидное значение enum; Project не может быть помечен удалённым при наличии активных Task или CR

#### ChangeRequest

- **Расположение:** `app/src/ProjectManagement/Domain/Entity/ChangeRequest.php`
- **Атрибуты:** `id: ChangeRequestId`, `projectId: ProjectId`, `name: string`, `description: ?string`, `deletedAt: ?DateTimeImmutable`
- **Фабричный метод `create()`:** валидирует, что `name` не пустой; устанавливает `deletedAt = null`
- **Фабричный метод `restore()`:** восстановление из хранилища
- **Метод `update(name, description)`:** обновление изменяемых полей; валидирует `name`. Поле `projectId` изменению не подлежит
- **Метод `softDelete()`:** устанавливает `deletedAt` в текущее время
- **Метод `isDeleted(): bool`:** проверяет `deletedAt !== null`
- **Инварианты:** `name` не может быть пустым; `projectId` не изменяется после создания (readonly); CR не может быть помечен удалённым при наличии привязанных Task

#### Task

- **Расположение:** `app/src/ProjectManagement/Domain/Entity/Task.php`
- **Атрибуты:** `id: TaskId`, `projectId: ?ProjectId`, `crId: ?ChangeRequestId`, `name: string`, `description: ?string`, `estimate: ?Estimate`, `deletedAt: ?DateTimeImmutable`
- **Фабричный метод `createForProject()`:** создание задачи, привязанной к Project; устанавливает `deletedAt = null`
- **Фабричный метод `createForChangeRequest()`:** создание задачи, привязанной к CR; устанавливает `deletedAt = null`
- **Фабричный метод `restore()`:** восстановление из хранилища
- **Метод `update(name, description, estimate)`:** обновление изменяемых полей; валидирует `name`. Поля `projectId` и `crId` изменению не подлежат
- **Метод `softDelete()`:** устанавливает `deletedAt` в текущее время
- **Метод `isDeleted(): bool`:** проверяет `deletedAt !== null`
- **Инварианты:** ровно одно из `projectId`/`crId` заполнено; `name` не может быть пустым; `estimate`, если указан, положителен (обеспечивается Value Object); Task не может быть помечена удалённой при наличии привязанных Ticket

---

## 4. Доменные репозитории

### Интерфейсы (Domain)

| Интерфейс | Расположение | Ключевые методы |
|---|---|---|
| `ClientRepositoryInterface` | `app/src/ProjectManagement/Domain/Repository/` | `save()`, `findById(ClientId)`, `remove()`, `findAll(criteria): array`, `countActiveProjectsByClientId(ClientId): int` |
| `ProjectRepositoryInterface` | `app/src/ProjectManagement/Domain/Repository/` | `save()`, `findById(ProjectId)`, `findAll(criteria): array`, `countActiveTasksByProjectId(ProjectId): int`, `countActiveChangeRequestsByProjectId(ProjectId): int` |
| `ChangeRequestRepositoryInterface` | `app/src/ProjectManagement/Domain/Repository/` | `save()`, `findById(ChangeRequestId)`, `findAll(criteria): array`, `countActiveTasksByChangeRequestId(ChangeRequestId): int` |
| `TaskRepositoryInterface` | `app/src/ProjectManagement/Domain/Repository/` | `save()`, `findById(TaskId)`, `findAll(criteria): array`, `hasTicketsForTask(TaskId): bool` |

**Примечания:**
- Все методы подсчёта (`countActiveProjectsByClientId`, `countActiveTasksByProjectId`, `countActiveChangeRequestsByProjectId`, `countActiveTasksByChangeRequestId`) считают только **активные** записи (`deletedAt IS NULL`) -- это критично для корректной работы soft-delete
- `hasTicketsForTask` -- проверка привязки Ticket через Persistence (прямой запрос к БД), без межсервисных вызовов. Реализуется в `TaskRepository`
- Метод `findAll` принимает критерии фильтрации и параметры пагинации
- `findAll` во всех репозиториях по умолчанию возвращает только активные (не удалённые, `deletedAt IS NULL`) записи
- Все методы оперируют доменными типами (Value Objects, Domain Entity)

### Реализации (Infrastructure)

Размещаются в `app/src/ProjectManagement/Infrastructure/Repository/`, используют composition с Doctrine Repository из `Persistence`. Выполняют маппинг Domain Entity <-> Doctrine Entity через методы `toOrmEntity()` и `toDomainEntity()`.

---

## 5. Use Cases

### Command Use Cases (создание/обновление/удаление)

| Use Case | Описание | InputDto | Зависимости |
|---|---|---|---|
| `CreateClientUseCase` | Создание клиента (UC-PM-01) | `name`, `?description` | `ClientRepositoryInterface` |
| `UpdateClientUseCase` | Обновление клиента (UC-PM-06) | `id`, `?name`, `?description` | `ClientRepositoryInterface` |
| `DeleteClientUseCase` | Soft-delete клиента (UC-PM-10) | `id` | `ClientRepositoryInterface` |
| `CreateProjectUseCase` | Создание проекта (UC-PM-02) | `clientId`, `name`, `status`, `?description` | `ProjectRepositoryInterface`, `ClientRepositoryInterface` |
| `UpdateProjectUseCase` | Обновление проекта (UC-PM-07) | `id`, `?name`, `?status`, `?description` | `ProjectRepositoryInterface` |
| `DeleteProjectUseCase` | Удаление проекта (UC-PM-11) | `id` | `ProjectRepositoryInterface` |
| `CreateChangeRequestUseCase` | Создание CR (UC-PM-03) | `projectId`, `name`, `?description` | `ChangeRequestRepositoryInterface`, `ProjectRepositoryInterface` |
| `UpdateChangeRequestUseCase` | Обновление CR (UC-PM-08) | `id`, `?name`, `?description` | `ChangeRequestRepositoryInterface` |
| `DeleteChangeRequestUseCase` | Soft-delete CR (UC-PM-12) | `id` | `ChangeRequestRepositoryInterface` |
| `CreateTaskUseCase` | Создание задачи (UC-PM-04) | `?projectId`, `?crId`, `name`, `?description`, `?estimate` | `TaskRepositoryInterface`, `ProjectRepositoryInterface`, `ChangeRequestRepositoryInterface` |
| `UpdateTaskUseCase` | Обновление задачи (UC-PM-09) | `id`, `?name`, `?description`, `?estimate` | `TaskRepositoryInterface` |
| `DeleteTaskUseCase` | Удаление задачи (UC-PM-13) | `id` | `TaskRepositoryInterface` |

### Query Use Cases (списки)

| Use Case | Описание | InputDto | OutputDto | Зависимости |
|---|---|---|---|---|
| `ListClientsUseCase` | Список клиентов (UC-PM-05) | `?name`, `page`, `perPage` | `items[]`, `total`, `page`, `perPage` | `ClientRepositoryInterface`, `ListClientsOutputPortInterface` |
| `ListProjectsUseCase` | Список проектов (UC-PM-05) | `?clientId`, `?status`, `page`, `perPage` | `items[]`, `total`, `page`, `perPage` | `ProjectRepositoryInterface`, `ListProjectsOutputPortInterface` |
| `ListChangeRequestsUseCase` | Список CR (UC-PM-14) | `?projectId`, `?name`, `page`, `perPage` | `items[]`, `total`, `page`, `perPage` | `ChangeRequestRepositoryInterface`, `ListChangeRequestsOutputPortInterface` |
| `ListTasksUseCase` | Список задач (UC-PM-05) | `?projectId`, `?crId`, `page`, `perPage` | `items[]`, `total`, `page`, `perPage` | `TaskRepositoryInterface`, `ListTasksOutputPortInterface` |

### Детали Use Cases

**CreateClientUseCase:**
1. Маппинг примитивов в Value Objects
2. Вызов `Client::create(name, description)`
3. Публикация события `ClientCreated`
4. Сохранение через `ClientRepositoryInterface::save()`

**UpdateClientUseCase (PUT-семантика):**
1. Поиск Client по ID; при отсутствии -- `ClientNotFoundException`
2. Проверка: Client не помечен удалённым (`deletedAt IS NULL`); при нарушении -- `EntityDeletedException` (ИН-09)
3. Применение правил PUT-семантики: переданные поля обновляются; не переданные -- остаются без изменений; явный `null` для `description` -- очищает значение; пустое тело -- без изменений (ответ 200/204)
4. Вызов `Client::update(name, description)`
5. Публикация события `ClientUpdated`
6. Сохранение через `ClientRepositoryInterface::save()`

**DeleteClientUseCase (Soft-delete):**
1. Поиск Client по ID; при отсутствии -- `ClientNotFoundException`
2. Проверка: Client не помечен удалённым
3. Проверка: `countActiveProjectsByClientId() === 0` (считаются только активные Project с `deletedAt IS NULL`)
4. При нарушении -- `ClientHasActiveProjectsException`
5. Вызов `Client::softDelete()`
6. Публикация события `ClientDeleted`
7. Сохранение через `ClientRepositoryInterface::save()`

**CreateProjectUseCase:**
1. Проверка существования Client по `clientId`; проверка, что Client не удалён
2. Вызов `Project::create(clientId, name, status, description)` -- Admin может создать Project в любом допустимом статусе (в том числе `Closed`)
3. Публикация события `ProjectCreated`
4. Сохранение через `ProjectRepositoryInterface::save()`

**UpdateProjectUseCase (PUT-семантика):**
1. Поиск Project по ID; при отсутствии -- `ProjectNotFoundException`
2. Проверка: Project не помечен удалённым (`deletedAt IS NULL`); при нарушении -- `EntityDeletedException` (ИН-09)
3. Применение правил PUT-семантики: переданные поля обновляются; не переданные -- остаются без изменений; явный `null` для `description` -- очищает значение; пустое тело -- без изменений (ответ 200/204)
4. Вызов `Project::update(name, status, description)`
5. Публикация события `ProjectUpdated`
6. Сохранение через `ProjectRepositoryInterface::save()`

**DeleteProjectUseCase (Soft-delete):**
1. Поиск Project по ID; при отсутствии -- `ProjectNotFoundException`
2. Проверка: Project не помечен удалённым
3. Проверка: `countActiveTasksByProjectId() === 0` и `countActiveChangeRequestsByProjectId() === 0` (считаются только активные записи с `deletedAt IS NULL`)
4. При нарушении -- `ProjectHasLinkedEntitiesException`
5. Вызов `Project::softDelete()`
6. Публикация события `ProjectDeleted`
7. Сохранение через `ProjectRepositoryInterface::save()`

**CreateChangeRequestUseCase:**
1. Проверка существования Project по `projectId`; проверка, что Project не удалён
2. Вызов `ChangeRequest::create(projectId, name, description)`
3. Публикация события `ChangeRequestCreated`
4. Сохранение через `ChangeRequestRepositoryInterface::save()`

**UpdateChangeRequestUseCase (PUT-семантика):**
1. Поиск CR по ID; при отсутствии -- `ChangeRequestNotFoundException`
2. Проверка: CR не помечен удалённым (`deletedAt IS NULL`); при нарушении -- `EntityDeletedException` (ИН-09)
3. Применение правил PUT-семантики: переданные поля обновляются; не переданные -- остаются без изменений; явный `null` для `description` -- очищает значение; пустое тело -- без изменений (ответ 200/204). Поле `projectId` изменению не подлежит
4. Вызов `ChangeRequest::update(name, description)`
5. Публикация события `ChangeRequestUpdated`
6. Сохранение через `ChangeRequestRepositoryInterface::save()`

**DeleteChangeRequestUseCase (Soft-delete):**
1. Поиск CR по ID; при отсутствии -- `ChangeRequestNotFoundException`
2. Проверка: CR не помечен удалённым
3. Проверка: `countActiveTasksByChangeRequestId() === 0` (считаются только активные Task с `deletedAt IS NULL`)
4. При нарушении -- `ChangeRequestHasLinkedTasksException`
5. Вызов `ChangeRequest::softDelete()`
6. Публикация события `ChangeRequestDeleted`
7. Сохранение через `ChangeRequestRepositoryInterface::save()`

**CreateTaskUseCase:**
1. Валидация: ровно одно из `projectId`/`crId` заполнено
2. Проверка существования родительского объекта (Project или CR); проверка, что родительский объект не удалён
3. Вызов `Task::createForProject()` или `Task::createForChangeRequest()`
4. Публикация события `TaskCreated`
5. Сохранение через `TaskRepositoryInterface::save()`

**UpdateTaskUseCase (PUT-семантика):**
1. Поиск Task по ID; при отсутствии -- `TaskNotFoundException`
2. Проверка: Task не помечена удалённой (`deletedAt IS NULL`); при нарушении -- `EntityDeletedException` (ИН-09)
3. Применение правил PUT-семантики: переданные поля обновляются; не переданные -- остаются без изменений; явный `null` для `description` и `estimate` -- очищает значение; пустое тело -- без изменений (ответ 200/204). Поля `projectId` и `crId` изменению не подлежат
4. Вызов `Task::update(name, description, estimate)`
5. Публикация события `TaskUpdated`
6. Сохранение через `TaskRepositoryInterface::save()`

**DeleteTaskUseCase (Soft-delete):**
1. Поиск Task по ID; при отсутствии -- `TaskNotFoundException`
2. Проверка: Task не помечена удалённой
3. Проверка: отсутствие привязанных Ticket через Persistence (прямой запрос к БД в `TaskRepositoryInterface::hasTicketsForTask()`)
4. При нарушении -- `TaskHasLinkedTicketsException`
5. Вызов `Task::softDelete()`
6. Публикация события `TaskDeleted`
7. Сохранение через `TaskRepositoryInterface::save()`

**ListClientsUseCase / ListProjectsUseCase / ListChangeRequestsUseCase / ListTasksUseCase:**
1. Маппинг фильтров из InputDto
2. Запрос к репозиторию с фильтрацией и пагинацией
3. Все списки возвращают только активные (не удалённые, `deletedAt IS NULL`) записи
4. Маппинг доменных объектов в OutputDto
5. Передача через `$this->presenter->present(OutputDto)`

---

## 6. Доменные события и аудит-логирование

### 6.1 Доменные события

| Событие | Расположение | Поля | Триггер |
|---|---|---|---|
| `ClientCreated` | `app/src/ProjectManagement/Domain/Event/` | `ClientId $clientId`, `string $name` | `CreateClientUseCase` |
| `ClientUpdated` | `app/src/ProjectManagement/Domain/Event/` | `ClientId $clientId` | `UpdateClientUseCase` |
| `ClientDeleted` | `app/src/ProjectManagement/Domain/Event/` | `ClientId $clientId` | `DeleteClientUseCase` |
| `ProjectCreated` | `app/src/ProjectManagement/Domain/Event/` | `ProjectId $projectId`, `ClientId $clientId`, `string $name`, `ProjectStatus $status` | `CreateProjectUseCase` |
| `ProjectUpdated` | `app/src/ProjectManagement/Domain/Event/` | `ProjectId $projectId` | `UpdateProjectUseCase` |
| `ProjectDeleted` | `app/src/ProjectManagement/Domain/Event/` | `ProjectId $projectId` | `DeleteProjectUseCase` |
| `ChangeRequestCreated` | `app/src/ProjectManagement/Domain/Event/` | `ChangeRequestId $changeRequestId`, `ProjectId $projectId`, `string $name` | `CreateChangeRequestUseCase` |
| `ChangeRequestUpdated` | `app/src/ProjectManagement/Domain/Event/` | `ChangeRequestId $changeRequestId` | `UpdateChangeRequestUseCase` |
| `ChangeRequestDeleted` | `app/src/ProjectManagement/Domain/Event/` | `ChangeRequestId $changeRequestId` | `DeleteChangeRequestUseCase` |
| `TaskCreated` | `app/src/ProjectManagement/Domain/Event/` | `TaskId $taskId`, `?ProjectId $projectId`, `?ChangeRequestId $crId`, `string $name` | `CreateTaskUseCase` |
| `TaskUpdated` | `app/src/ProjectManagement/Domain/Event/` | `TaskId $taskId` | `UpdateTaskUseCase` |
| `TaskDeleted` | `app/src/ProjectManagement/Domain/Event/` | `TaskId $taskId` | `DeleteTaskUseCase` |

Все события -- `final readonly class`, содержат `DateTimeImmutable $occurredAt`.

**Примечание:** события создания (Created) содержат ключевые атрибуты сущности; события обновления (Updated) и удаления (Deleted) содержат только идентификатор сущности -- это намеренное решение (минимальный контракт). Подписчик при необходимости может запросить полные данные сущности через API.

Сущности используют трейт `RecordsDomainEvents` для накопления событий. Публикация событий -- через Symfony Event Dispatcher.

### 6.2 Аудит-логирование (НФТ-Б-06)

**Механизм:** Event Listener на доменные события, запись через Symfony Logger (Monolog).

**Расположение:** `app/src/ProjectManagement/Infrastructure/EventListener/AuditLogEventListener.php`

**Принцип работы:**
1. При публикации доменного события (ClientCreated/Updated/Deleted, ProjectCreated/Updated/Deleted, ChangeRequestCreated/Updated/Deleted, TaskCreated/Updated/Deleted) Event Listener перехватывает событие
2. Формирует структурированную запись аудит-лога
3. Записывает через `LoggerInterface` (Monolog)

**Состав записи аудит-лога:**
- Идентификатор пользователя, выполнившего операцию
- Тип действия: `create`, `update`, `delete`
- Тип сущности и её идентификатор
- Временная метка (UTC)
- Результат операции (`success` / `failure`) и код ошибки при неуспехе

**Место хранения:** стандартный лог-файл Symfony (либо stdout в зависимости от конфигурации окружения). Отдельная таблица `audit_log` в базе данных **не создаётся**. Просмотр аудит-лога через API **не предусмотрен**.

**Зависимости:** `Psr\Log\LoggerInterface`, `Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface` (для получения текущего пользователя).

---

## 7. Доменные исключения

| Исключение | Когда выбрасывается |
|---|---|
| `ClientNotFoundException` | Client не найден по ID |
| `ProjectNotFoundException` | Project не найден по ID |
| `ChangeRequestNotFoundException` | CR не найден по ID |
| `TaskNotFoundException` | Task не найдена по ID |
| `ClientHasActiveProjectsException` | Попытка soft-delete Client с активными Project |
| `ProjectHasLinkedEntitiesException` | Попытка soft-delete Project с активными Task или CR |
| `ChangeRequestHasLinkedTasksException` | Попытка soft-delete CR с привязанными Task |
| `TaskHasLinkedTicketsException` | Попытка soft-delete Task с привязанными Ticket |
| `InvalidTaskParentException` | Нарушен инвариант: ни одно или оба поля projectId/crId заполнены |
| `EntityDeletedException` | Попытка обновить (Update) сущность, помеченную удалённой (`deletedAt IS NOT NULL`). Инвариант ИН-09. Сообщение: «Сущность удалена и не может быть изменена» |

Все исключения наследуют `\DomainException`.

---

## 8. Persistence (Doctrine)

### Doctrine Entities

#### `Persistence\Entity\Client`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `name` | `string(255)` | NOT NULL |
| `description` | `text` | NULLABLE |
| `deleted_at` | `datetime_immutable` | NULLABLE |
| `created_at` | `datetime_immutable` | NOT NULL |
| `updated_at` | `datetime_immutable` | NULLABLE |

**Индекс:** на `deleted_at` (для фильтрации активных клиентов).

#### `Persistence\Entity\Project`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `client_id` | `guid` | NOT NULL, FK -> clients, INDEX |
| `name` | `string(255)` | NOT NULL |
| `status` | `string(20)` | NOT NULL, `enumType: ProjectStatus` |
| `description` | `text` | NULLABLE |
| `deleted_at` | `datetime_immutable` | NULLABLE |
| `created_at` | `datetime_immutable` | NOT NULL |
| `updated_at` | `datetime_immutable` | NULLABLE |

**Индекс:** на `deleted_at` (для фильтрации активных проектов).

#### `Persistence\Entity\ChangeRequest`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `project_id` | `guid` | NOT NULL, FK -> projects, INDEX |
| `name` | `string(255)` | NOT NULL |
| `description` | `text` | NULLABLE |
| `deleted_at` | `datetime_immutable` | NULLABLE |
| `created_at` | `datetime_immutable` | NOT NULL |
| `updated_at` | `datetime_immutable` | NULLABLE |

**Индекс:** на `deleted_at` (для фильтрации активных CR).

#### `Persistence\Entity\Task`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `project_id` | `guid` | NULLABLE, FK -> projects, INDEX |
| `cr_id` | `guid` | NULLABLE, FK -> change_requests, INDEX |
| `name` | `string(255)` | NOT NULL |
| `description` | `text` | NULLABLE |
| `estimate` | `decimal(8,2)` | NULLABLE |
| `deleted_at` | `datetime_immutable` | NULLABLE |
| `created_at` | `datetime_immutable` | NOT NULL |
| `updated_at` | `datetime_immutable` | NULLABLE |

**CHECK constraint:** ровно одно из `project_id`/`cr_id` NOT NULL (на уровне миграции).
**Индекс:** на `deleted_at` (для фильтрации активных задач).

### Doctrine Repositories

Каждый наследует `ServiceEntityRepository`, содержит:
- `save(Entity, flush)`, `remove(Entity, flush)`
- Методы поиска с фильтрацией: `findByName()`, `findByClientId()`, `findByStatus()`, `findByProjectId()`, `findByCrId()`
- Методы подсчёта активных записей (`deleted_at IS NULL`): `countActiveByClientId()`, `countActiveTasksByProjectId()`, `countActiveChangeRequestsByProjectId()`, `countActiveTasksByChangeRequestId()`
- Методы с пагинацией (QueryBuilder + `setFirstResult()`/`setMaxResults()`)
- Все репозитории: методы `findAll` фильтруют по `deleted_at IS NULL` (возвращают только активные записи)
- `TaskRepository`: метод `hasTicketsForTask()` -- прямой запрос к таблице `tickets` для проверки привязки Ticket

---

## 9. Infrastructure (HTTP-слой)

### API-маршруты

| Метод | Путь | Контроллер | Имя маршрута |
|---|---|---|---|
| POST | `/api/project-management/clients` | `CreateClientController` | `project_management_create_client` |
| GET | `/api/project-management/clients` | `ListClientsController` | `project_management_list_clients` |
| PUT | `/api/project-management/clients/{id}` | `UpdateClientController` | `project_management_update_client` |
| DELETE | `/api/project-management/clients/{id}` | `DeleteClientController` | `project_management_delete_client` |
| POST | `/api/project-management/projects` | `CreateProjectController` | `project_management_create_project` |
| GET | `/api/project-management/projects` | `ListProjectsController` | `project_management_list_projects` |
| PUT | `/api/project-management/projects/{id}` | `UpdateProjectController` | `project_management_update_project` |
| DELETE | `/api/project-management/projects/{id}` | `DeleteProjectController` | `project_management_delete_project` |
| POST | `/api/project-management/change-requests` | `CreateChangeRequestController` | `project_management_create_change_request` |
| GET | `/api/project-management/change-requests` | `ListChangeRequestsController` | `project_management_list_change_requests` |
| PUT | `/api/project-management/change-requests/{id}` | `UpdateChangeRequestController` | `project_management_update_change_request` |
| DELETE | `/api/project-management/change-requests/{id}` | `DeleteChangeRequestController` | `project_management_delete_change_request` |
| POST | `/api/project-management/tasks` | `CreateTaskController` | `project_management_create_task` |
| GET | `/api/project-management/tasks` | `ListTasksController` | `project_management_list_tasks` |
| PUT | `/api/project-management/tasks/{id}` | `UpdateTaskController` | `project_management_update_task` |
| DELETE | `/api/project-management/tasks/{id}` | `DeleteTaskController` | `project_management_delete_task` |

### Request DTO с валидацией

**CreateClientRequestDto:**
- `name: string` -- `#[NotBlank]`, `#[Length(max: 255)]`
- `description: ?string` -- `#[Length(max: 1000)]`

**UpdateClientRequestDto:**
- `name: ?string` -- `#[NotBlank]` (если передан), `#[Length(max: 255)]`
- `description: ?string` -- `#[Length(max: 1000)]`

**CreateProjectRequestDto:**
- `clientId: string` -- `#[NotBlank]`, `#[Uuid]`
- `name: string` -- `#[NotBlank]`, `#[Length(max: 255)]`
- `status: string` -- `#[NotBlank]`, `#[Choice(callback: [ProjectStatus::class, 'values'])]`
- `description: ?string` -- `#[Length(max: 1000)]`

**UpdateProjectRequestDto:**
- `name: ?string` -- `#[NotBlank]` (если передан), `#[Length(max: 255)]`
- `status: ?string` -- `#[Choice(callback: [ProjectStatus::class, 'values'])]`
- `description: ?string` -- `#[Length(max: 1000)]`

**CreateChangeRequestRequestDto:**
- `projectId: string` -- `#[NotBlank]`, `#[Uuid]`
- `name: string` -- `#[NotBlank]`, `#[Length(max: 255)]`
- `description: ?string` -- `#[Length(max: 1000)]`

**UpdateChangeRequestRequestDto:**
- `name: ?string` -- `#[NotBlank]` (если передан), `#[Length(max: 255)]`
- `description: ?string` -- `#[Length(max: 1000)]`

**CreateTaskRequestDto:**
- `projectId: ?string` -- `#[Uuid]`
- `crId: ?string` -- `#[Uuid]`
- `name: string` -- `#[NotBlank]`, `#[Length(max: 255)]`
- `description: ?string` -- `#[Length(max: 1000)]`
- `estimate: ?float` -- `#[Positive]`

**UpdateTaskRequestDto:**
- `name: ?string` -- `#[NotBlank]` (если передан), `#[Length(max: 255)]`
- `description: ?string` -- `#[Length(max: 1000)]`
- `estimate: ?float` -- `#[Positive]`

**Кастомный валидатор `ExactlyOneParent`:** CLASS_CONSTRAINT на `CreateTaskRequestDto` -- проверяет, что ровно одно из полей `projectId`/`crId` заполнено.

**ListClientsRequestDto** (GET, query-параметры):
- `name: ?string` -- `#[Length(max: 255)]`
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

**ListProjectsRequestDto** (GET, query-параметры):
- `clientId: ?string` -- `#[Uuid]`
- `status: ?string` -- `#[Choice]`
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

**ListChangeRequestsRequestDto** (GET, query-параметры):
- `projectId: ?string` -- `#[Uuid]`
- `name: ?string` -- `#[Length(max: 255)]`
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

**ListTasksRequestDto** (GET, query-параметры):
- `projectId: ?string` -- `#[Uuid]`
- `crId: ?string` -- `#[Uuid]`
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

### Response DTO

Каждый Response DTO содержит OpenAPI-атрибуты (`#[OA\Schema]`, `#[OA\Property]`).

**ClientResponseDto:** `id`, `name`, `description`
**ProjectResponseDto:** `id`, `clientId`, `clientName`, `name`, `status`, `statusLabel`, `description`
**ChangeRequestResponseDto:** `id`, `projectId`, `projectName`, `name`, `description`
**TaskResponseDto:** `id`, `projectId`, `projectName`, `crId`, `crName`, `name`, `description`, `estimate`

List Response DTO содержат `items[]`, `total`, `page`, `perPage`.

**Важно:** согласно НФТ-У-02, ответы содержат наименования связанных сущностей (clientName, projectName, crName), а не только их идентификаторы.

### Порт к TimesheetContext

Проверка привязки Ticket к Task при удалении реализуется через **Persistence** -- прямой запрос к таблице `tickets` в базе данных. Это не межсервисный вызов, а прямой SQL-запрос через Doctrine, так как все данные хранятся в одной базе.

Метод `hasTicketsForTask(TaskId): bool` размещён в `TaskRepositoryInterface` (Domain) и реализуется в `TaskRepository` (Infrastructure).

---

## 10. Зависимости между компонентами

### Граф зависимостей (направление: зависимый -> от чего зависит)

```
Domain Layer (нет внешних зависимостей):
  Entity -> ValueObject, Enum, Exception
  Repository Interface -> Entity, ValueObject

Application Layer (зависит от Domain):
  UseCase -> Repository Interface, Entity, ValueObject, Enum, Exception
  InputDto -> (только примитивы, нет зависимостей)
  OutputDto -> (только примитивы, нет зависимостей)
  OutputPort -> OutputDto

Infrastructure Layer (зависит от Domain + Application):
  Controller -> UseCase, Transformer, Presenter, RequestDto
  Transformer -> RequestDto, InputDto
  Presenter -> OutputDto, OutputPort, ResponseDto
  ValueResolver -> AbstractJsonValueResolver/AbstractValueResolver, RequestDto
  DoctrineRepository -> Domain Repository Interface, Domain Entity, Domain VO, Persistence Entity, Persistence Repository
  Validator -> RequestDto
  AuditLogEventListener -> Domain Event (12 событий), LoggerInterface, TokenStorageInterface

Persistence Layer (зависит от Doctrine):
  Doctrine Entity -> ORM Mapping, Domain Enum
  Doctrine Repository -> Doctrine Entity, ServiceEntityRepository
```

### Зависимости от существующей инфраструктуры

- `App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver` -- для POST/PUT-запросов
- `App\Shared\Infrastructure\ValueResolver\AbstractValueResolver` -- для GET-запросов (List)
- `App\Persistence\Entity\*` и `App\Persistence\Repository\*` -- Doctrine-слой
- Symfony Framework: Route, AbstractController, JsonResponse, Validator, Serializer, EventDispatcher, Security, Logger (Monolog)

### Межконтекстные зависимости

- **TimesheetContext -> ProjectManagement:** TimesheetContext использует `taskId` для привязки Ticket к Task
- **ProjectManagement -> Persistence:** при удалении Task проверка наличия Ticket выполняется через прямой запрос к таблице `tickets` (Persistence), без межсервисных вызовов

---

## 11. Порядок реализации

### Этап 1: Domain Layer

**Шаг 1.1:** Enum
- `ProjectStatus`

**Шаг 1.2:** Value Objects
- `ClientId`, `ProjectId`, `ChangeRequestId`, `TaskId`, `Estimate`

**Шаг 1.3:** Доменные исключения
- Все исключения из раздела 7 (включая `ChangeRequestHasLinkedTasksException`, `EntityDeletedException`)

**Шаг 1.4:** Доменные события
- Created: `ClientCreated`, `ProjectCreated`, `ChangeRequestCreated`, `TaskCreated`
- Updated: `ClientUpdated`, `ProjectUpdated`, `ChangeRequestUpdated`, `TaskUpdated`
- Deleted: `ClientDeleted`, `ProjectDeleted`, `ChangeRequestDeleted`, `TaskDeleted`

**Шаг 1.5:** Domain Entities
- `Client` (с `deletedAt`, методами `softDelete()`, `update()`)
- `Project` (с `deletedAt`, методами `softDelete()`, `update()`)
- `ChangeRequest` (с `deletedAt`, методами `softDelete()`, `update()`)
- `Task` (с `deletedAt`, методами `softDelete()`, `update()`)

**Шаг 1.6:** Интерфейсы репозиториев
- `ClientRepositoryInterface` (с `countActiveProjectsByClientId`)
- `ProjectRepositoryInterface` (с `countActiveTasksByProjectId`, `countActiveChangeRequestsByProjectId`)
- `ChangeRequestRepositoryInterface` (с `countActiveTasksByChangeRequestId`)
- `TaskRepositoryInterface` (с `hasTicketsForTask`)

### Этап 2: Persistence Layer

**Шаг 2.1:** Doctrine Entities
- `Persistence\Entity\Client` (с `deleted_at`), `Project` (с `deleted_at`), `ChangeRequest` (с `deleted_at`), `Task` (с `deleted_at`)

**Шаг 2.2:** Doctrine Repositories
- `Persistence\Repository\ClientRepository`, `ProjectRepository`, `ChangeRequestRepository`, `TaskRepository`

**Шаг 2.3:** Миграции
- Генерация через `doctrine:migrations:diff`

### Этап 3: Application Layer

**Шаг 3.1:** InputDto и OutputDto
- Все DTO из раздела 5 (включая Update-DTO и CR List DTO)

**Шаг 3.2:** OutputPort-интерфейсы
- `ListClientsOutputPortInterface`, `ListProjectsOutputPortInterface`, `ListChangeRequestsOutputPortInterface`, `ListTasksOutputPortInterface`

**Шаг 3.3:** Command Use Cases -- создание
- `CreateClientUseCase`, `CreateProjectUseCase`, `CreateChangeRequestUseCase`, `CreateTaskUseCase`

**Шаг 3.4:** Command Use Cases -- обновление
- `UpdateClientUseCase`, `UpdateProjectUseCase`, `UpdateChangeRequestUseCase`, `UpdateTaskUseCase`

**Шаг 3.5:** Command Use Cases -- удаление (все soft-delete)
- `DeleteClientUseCase`, `DeleteProjectUseCase`, `DeleteChangeRequestUseCase`, `DeleteTaskUseCase`

**Шаг 3.6:** Query Use Cases
- `ListClientsUseCase`, `ListProjectsUseCase`, `ListChangeRequestsUseCase`, `ListTasksUseCase`

### Этап 4: Infrastructure Layer

**Шаг 4.1:** Реализации доменных репозиториев
- `ClientRepository`, `ProjectRepository`, `ChangeRequestRepository`, `TaskRepository`

**Шаг 4.2:** Кастомные валидаторы
- `ExactlyOneParent` / `ExactlyOneParentValidator` (для CreateTaskRequestDto)

**Шаг 4.3:** Request DTO
- Все Request DTO с атрибутами валидации (включая Update-DTO и ListChangeRequestsRequestDto)

**Шаг 4.4:** Response DTO
- Все Response DTO с OpenAPI-атрибутами (включая `ChangeRequestResponseDto`, `ChangeRequestListResponseDto`)

**Шаг 4.5:** Value Resolvers
- По одному на каждый Request DTO (включая Update и ListChangeRequests)

**Шаг 4.6:** Input Transformers
- По одному на каждый Use Case (включая Update и ListChangeRequests/DeleteChangeRequest)

**Шаг 4.7:** Presenters
- `HttpListClientsPresenter`, `HttpListProjectsPresenter`, `HttpListChangeRequestsPresenter`, `HttpListTasksPresenter`

**Шаг 4.8:** Controllers
- Все 16 контроллеров (4 сущности x 4 операции: Create, List, Update, Delete)

**Шаг 4.9:** Event Listener для аудит-логирования
- `AuditLogEventListener` -- подписка на все 12 доменных событий (Created/Updated/Deleted x 4 сущности), запись через Monolog

**Шаг 4.10:** DI-конфигурация
- Alias-привязки интерфейсов к реализациям в `config/services.php`
- Регистрация Event Listener

### Этап 5: Тестирование

**Шаг 5.1:** Unit-тесты Domain (Entity, VO, Enum)
**Шаг 5.2:** Unit-тесты Application (Use Cases, включая Update и Delete CR)
**Шаг 5.3:** Unit-тесты Infrastructure (Transformers, Presenters, Validators, Repositories, AuditLogEventListener)
**Шаг 5.4:** Интеграционные тесты (Doctrine Repositories)
**Шаг 5.5:** Функциональные тесты (Controllers через WebTestCase)

---

## 12. Тестирование

### Unit-тесты Domain

| Тест | Что проверяется |
|---|---|
| `ClientTest` | Создание с валидным именем; отклонение пустого имени; `restore()`; `update()` с валидным/пустым именем; `softDelete()`; `isDeleted()` |
| `ProjectTest` | Создание с валидными данными (включая `Closed` статус); отклонение пустого имени; `isActive()`; `update()` с валидным/пустым именем и статусом; `softDelete()`; `isDeleted()` |
| `ChangeRequestTest` | Создание; неизменяемость `projectId`; `update()` с валидным/пустым именем; `softDelete()`; `isDeleted()` |
| `TaskTest` | `createForProject()`; `createForChangeRequest()`; отклонение создания без родителя; `update()` с валидным/пустым именем, estimate; `softDelete()`; `isDeleted()` |
| `ProjectStatusTest` | `getLabel()` для каждого кейса; `from()`; `tryFrom()` |
| `EstimateTest` | Создание с валидным значением; отклонение нулевого/отрицательного |
| ID Value Objects | Валидация UUID; `equals()`; `generate()` |

### Unit-тесты Application

| Тест | Что проверяется |
|---|---|
| `CreateClientUseCaseTest` | Вызов `save()` с доменным объектом; маппинг InputDto -> VO; публикация `ClientCreated` |
| `UpdateClientUseCaseTest` | Успешное обновление; выброс `EntityDeletedException` при обновлении удалённого Client (ИН-09); выброс `ClientNotFoundException`; публикация `ClientUpdated` |
| `DeleteClientUseCaseTest` | Успешный soft-delete; выброс `ClientNotFoundException`; выброс `ClientHasActiveProjectsException`; публикация `ClientDeleted` |
| `CreateProjectUseCaseTest` | Создание проекта в статусе `Active`; создание в `Closed`; проверка существования Client; отклонение удалённого Client; публикация `ProjectCreated` |
| `UpdateProjectUseCaseTest` | Успешное обновление; выброс `EntityDeletedException` при обновлении удалённого Project (ИН-09); выброс `ProjectNotFoundException`; публикация `ProjectUpdated` |
| `DeleteProjectUseCaseTest` | Успешный soft-delete; отклонение soft-delete удалённого Project; выброс при активных Task/CR; публикация `ProjectDeleted` |
| `CreateChangeRequestUseCaseTest` | Создание CR; проверка существования Project; отклонение удалённого Project; публикация `ChangeRequestCreated` |
| `UpdateChangeRequestUseCaseTest` | Успешное обновление; выброс `EntityDeletedException` при обновлении удалённого CR (ИН-09); выброс `ChangeRequestNotFoundException`; публикация `ChangeRequestUpdated` |
| `DeleteChangeRequestUseCaseTest` | Успешный soft-delete; выброс `ChangeRequestNotFoundException`; выброс `ChangeRequestHasLinkedTasksException`; публикация `ChangeRequestDeleted` |
| `CreateTaskUseCaseTest` | Создание для Project; создание для CR; проверка существования родителя; отклонение привязки к удалённому родителю; публикация `TaskCreated` |
| `UpdateTaskUseCaseTest` | Успешное обновление; выброс `EntityDeletedException` при обновлении удалённой Task (ИН-09); выброс `TaskNotFoundException`; публикация `TaskUpdated` |
| `DeleteTaskUseCaseTest` | Успешный soft-delete; отклонение soft-delete удалённой Task; выброс при привязанных Ticket; публикация `TaskDeleted` |
| `ListClientsUseCaseTest` | Вызов `present()` с корректным OutputDto; пустой результат; фильтрация исключает удалённых |
| `ListProjectsUseCaseTest` | Фильтрация по clientId/status; пагинация |
| `ListChangeRequestsUseCaseTest` | Фильтрация по projectId/name; пагинация; фильтрация исключает удалённых |
| `ListTasksUseCaseTest` | Фильтрация по projectId/crId; пагинация |

### Unit-тесты Infrastructure

| Тест | Что проверяется |
|---|---|
| `AuditLogEventListenerTest` | Обработка всех 12 событий (Created/Updated/Deleted x 4 сущности); формирование корректной записи аудит-лога; наличие userId, action, entityType, entityId, timestamp, result |

### Интеграционные тесты Persistence

| Тест | Что проверяется |
|---|---|
| `ClientRepositoryTest` | CRUD операции; `findByName()`; `countActiveByClientId()`; soft-delete (фильтрация по `deleted_at`) |
| `ProjectRepositoryTest` | CRUD; фильтрация по `clientId`/`status`; soft-delete (фильтрация по `deleted_at`); `countActiveTasksByProjectId()`; `countActiveChangeRequestsByProjectId()` |
| `ChangeRequestRepositoryTest` | CRUD; привязка к Project; soft-delete (фильтрация по `deleted_at`); `countActiveTasksByChangeRequestId()` |
| `TaskRepositoryTest` | CRUD; фильтрация по `projectId`/`crId`; CHECK constraint; soft-delete (фильтрация по `deleted_at`); `hasTicketsForTask()` |
