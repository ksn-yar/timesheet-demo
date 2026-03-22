# План реализации домена Identity

**Версия:** 1.0
**Дата:** 2026-03-22
**Основание:** docs/technical-specification-parts/identity.md (v1.1)

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

Домен Identity реализуется как отдельный Bounded Context `Identity` в рамках существующей чистой архитектуры проекта. Структура следует принципам Clean Architecture и DDD:

- **Domain** -- сущности, Value Objects, Enum, интерфейсы репозиториев, доменные события, исключения
- **Application** -- Use Cases, InputDto, OutputDto, OutputPort-интерфейсы
- **Infrastructure** -- контроллеры, DTO запросов/ответов, Value Resolvers, Input Transformers, Presenters, реализации репозиториев, Event Listener для аудит-логирования

Persistence-слой (Doctrine Entity и Repository) размещается в домене `Persistence` -- архитектурный компромисс проекта.

---

## 2. Структура модулей и файлов

### Bounded Context: Identity

```
app/src/
├── Identity/
│   ├── Domain/
│   │   ├── Entity/
│   │   │   ├── User.php
│   │   │   └── Group.php
│   │   ├── ValueObject/
│   │   │   ├── UserId.php
│   │   │   ├── GroupId.php
│   │   │   ├── Email.php
│   │   │   └── HashedPassword.php
│   │   ├── Enum/
│   │   │   └── SystemRole.php
│   │   ├── Repository/
│   │   │   ├── UserRepositoryInterface.php
│   │   │   └── GroupRepositoryInterface.php
│   │   ├── Event/
│   │   │   ├── UserCreated.php
│   │   │   ├── UserDeactivated.php
│   │   │   ├── UserDeleted.php
│   │   │   ├── UserAssignedToGroup.php
│   │   │   └── GroupCreated.php
│   │   └── Exception/
│   │       ├── UserNotFoundException.php
│   │       ├── GroupNotFoundException.php
│   │       ├── DuplicateEmailException.php
│   │       ├── DuplicateGroupNameException.php
│   │       ├── UserHasLinkedTicketsException.php
│   │       ├── GroupHasActiveUsersException.php
│   │       ├── UserAlreadyDeactivatedException.php
│   │       └── EntityDeletedException.php
│   ├── Application/
│   │   ├── UseCase/
│   │   │   ├── CreateUserUseCase.php
│   │   │   ├── DeactivateUserUseCase.php
│   │   │   ├── DeleteUserUseCase.php
│   │   │   ├── AssignUserToGroupUseCase.php
│   │   │   ├── ChangeUserGroupUseCase.php
│   │   │   ├── ListUsersUseCase.php
│   │   │   ├── CreateGroupUseCase.php
│   │   │   ├── DeleteGroupUseCase.php
│   │   │   └── ListGroupsUseCase.php
│   │   ├── Dto/
│   │   │   ├── CreateUserInputDto.php
│   │   │   ├── DeactivateUserInputDto.php
│   │   │   ├── DeleteUserInputDto.php
│   │   │   ├── AssignUserToGroupInputDto.php
│   │   │   ├── ChangeUserGroupInputDto.php
│   │   │   ├── ListUsersInputDto.php
│   │   │   ├── ListUsersOutputDto.php
│   │   │   ├── CreateGroupInputDto.php
│   │   │   ├── DeleteGroupInputDto.php
│   │   │   ├── ListGroupsInputDto.php
│   │   │   └── ListGroupsOutputDto.php
│   │   └── Port/
│   │       ├── ListUsersOutputPortInterface.php
│   │       ├── ListGroupsOutputPortInterface.php
│   │       ├── TicketExistenceCheckerInterface.php
│   │       └── PasswordHasherInterface.php
│   └── Infrastructure/
│       ├── Controller/
│       │   ├── CreateUserController.php
│       │   ├── DeactivateUserController.php
│       │   ├── DeleteUserController.php
│       │   ├── AssignUserToGroupController.php
│       │   ├── ChangeUserGroupController.php
│       │   ├── ListUsersController.php
│       │   ├── CreateGroupController.php
│       │   ├── DeleteGroupController.php
│       │   └── ListGroupsController.php
│       ├── Dto/
│       │   ├── CreateUserRequestDto.php
│       │   ├── AssignUserToGroupRequestDto.php
│       │   ├── ChangeUserGroupRequestDto.php
│       │   ├── ListUsersRequestDto.php
│       │   ├── ListGroupsRequestDto.php
│       │   ├── CreateGroupRequestDto.php
│       │   ├── UserResponseDto.php
│       │   ├── GroupResponseDto.php
│       │   ├── UserListResponseDto.php
│       │   └── GroupListResponseDto.php
│       ├── ValueResolver/
│       │   ├── CreateUserValueResolver.php
│       │   ├── AssignUserToGroupValueResolver.php
│       │   ├── ChangeUserGroupValueResolver.php
│       │   ├── ListUsersValueResolver.php
│       │   ├── ListGroupsValueResolver.php
│       │   └── CreateGroupValueResolver.php
│       ├── Transformer/
│       │   ├── CreateUserInputTransformer.php
│       │   ├── DeactivateUserInputTransformer.php
│       │   ├── DeleteUserInputTransformer.php
│       │   ├── AssignUserToGroupInputTransformer.php
│       │   ├── ChangeUserGroupInputTransformer.php
│       │   ├── ListUsersInputTransformer.php
│       │   ├── CreateGroupInputTransformer.php
│       │   ├── DeleteGroupInputTransformer.php
│       │   └── ListGroupsInputTransformer.php
│       ├── Presenter/
│       │   ├── HttpListUsersPresenter.php
│       │   └── HttpListGroupsPresenter.php
│       ├── EventListener/
│       │   └── AuditLogEventListener.php
│       ├── Repository/
│       │   ├── DoctrineUserRepository.php
│       │   └── DoctrineGroupRepository.php
│       └── Security/
│           └── SymfonyPasswordHasher.php
```

### Persistence

```
app/src/
├── Persistence/
│   ├── Entity/
│   │   ├── User.php
│   │   └── Group.php
│   └── Repository/
│       ├── UserRepository.php
│       └── GroupRepository.php
```

---

## 3. Сущности, Value Objects, Enum

### 3.1 Enum

#### SystemRole

- **Расположение:** `app/src/Identity/Domain/Enum/SystemRole.php`
- **Тип:** `enum SystemRole: string`
- **Кейсы:** `Admin = 'admin'`, `Manager = 'manager'`, `Employee = 'employee'`
- **Методы:** `getLabel(): string` (русскоязычные метки: "Администратор", "Менеджер", "Сотрудник")

### 3.2 Value Objects

| Value Object | Расположение | Описание |
|---|---|---|
| `UserId` | `app/src/Identity/Domain/ValueObject/UserId.php` | Типизированный UUID-идентификатор пользователя |
| `GroupId` | `app/src/Identity/Domain/ValueObject/GroupId.php` | Типизированный UUID-идентификатор группы |
| `Email` | `app/src/Identity/Domain/ValueObject/Email.php` | Email пользователя с валидацией формата |
| `HashedPassword` | `app/src/Identity/Domain/ValueObject/HashedPassword.php` | Хэш пароля; никогда не содержит открытый текст |

Каждый ID Value Object:
- Принимает `string $value` в конструкторе
- Валидирует формат UUID
- Предоставляет методы `value(): string` и `equals(self $other): bool`
- Имеет статический метод `generate(): self` для создания нового UUID

`Email`:
- Принимает `string $value` в конструкторе
- Валидирует формат email (filter_var FILTER_VALIDATE_EMAIL)
- Хранит значение в нижнем регистре (нормализация)
- Предоставляет методы `value(): string` и `equals(self $other): bool`

`HashedPassword`:
- Принимает `string $hash` в конструкторе (уже хэшированное значение)
- Предоставляет метод `value(): string`
- Не содержит логику хэширования -- для этого используется `PasswordHasherInterface`

### 3.3 Domain Entities (Aggregate Roots)

#### User

- **Расположение:** `app/src/Identity/Domain/Entity/User.php`
- **Атрибуты:** `id: UserId`, `name: string`, `email: Email`, `passwordHash: HashedPassword`, `systemRole: SystemRole`, `groupId: ?GroupId`, `roleId: ?string` (UUID-строка, ссылка на Role из WorkCatalogContext), `isActive: bool`, `deletedAt: ?DateTimeImmutable`
- **Фабричный метод `create()`:** валидирует, что `name` не пустой; устанавливает `isActive = true`, `deletedAt = null`
- **Фабричный метод `restore()`:** восстановление из хранилища без валидации
- **Метод `deactivate()`:** устанавливает `isActive = false`
- **Метод `softDelete()`:** устанавливает `deletedAt` в текущее время
- **Метод `isDeleted(): bool`:** проверяет `deletedAt !== null`
- **Метод `assignToGroup(GroupId $groupId)`:** назначает User в Group
- **Метод `removeFromGroup()`:** снимает привязку к Group (`groupId = null`)
- **Инварианты:**
  - `name` не может быть пустым
  - `email` должен быть уникальным в системе (проверяется на уровне репозитория)
  - Soft-delete недоступен, если к User привязаны Ticket (проверяется на уровне Use Case)
  - Пароль хранится только в хэшированном виде

#### Group

- **Расположение:** `app/src/Identity/Domain/Entity/Group.php`
- **Атрибуты:** `id: GroupId`, `name: string`, `description: ?string`, `deletedAt: ?DateTimeImmutable`
- **Фабричный метод `create()`:** валидирует, что `name` не пустой; устанавливает `deletedAt = null`
- **Фабричный метод `restore()`:** восстановление из хранилища без валидации
- **Метод `softDelete()`:** устанавливает `deletedAt` в текущее время
- **Метод `isDeleted(): bool`:** проверяет `deletedAt !== null`
- **Инварианты:**
  - `name` должен быть уникальным (проверяется на уровне репозитория)
  - Group не может быть помечена удалённой при наличии активных User

---

## 4. Доменные репозитории

### Интерфейсы (Domain)

| Интерфейс | Расположение | Ключевые методы |
|---|---|---|
| `UserRepositoryInterface` | `app/src/Identity/Domain/Repository/` | `save()`, `findById(UserId)`, `findAll(criteria): array`, `existsByEmail(Email): bool`, `countActiveUsersByGroupId(GroupId): int` |
| `GroupRepositoryInterface` | `app/src/Identity/Domain/Repository/` | `save()`, `findById(GroupId)`, `findAll(criteria): array`, `existsByName(string): bool` |

**Примечания:**
- `existsByEmail` -- проверка уникальности email. При обновлении User (если будет добавлено) должен исключать текущего User из проверки
- `countActiveUsersByGroupId` -- подсчёт активных (`isActive = true`) пользователей в группе. Soft-deleted пользователи не учитываются
- `existsByName` -- проверка уникальности наименования Group. Soft-deleted Group не учитываются
- `findAll` во всех репозиториях по умолчанию возвращает только активные (не удалённые, `deletedAt IS NULL`) записи
- Все методы оперируют доменными типами (Value Objects, Domain Entity)

### Порт к TimesheetContext

Проверка привязки Ticket к User при soft-delete реализуется через **Persistence** -- прямой запрос к таблице `tickets` в базе данных. Это не межсервисный вызов, а прямой SQL-запрос через Doctrine, так как все данные хранятся в одной базе.

| Интерфейс | Расположение | Метод |
|---|---|---|
| `TicketExistenceCheckerInterface` | `app/src/Identity/Application/Port/` | `hasTicketsForUser(UserId): bool` |

Реализация размещается в `DoctrineUserRepository`.

### Порт к WorkCatalogContext

Ссылка `roleId` у User -- это опциональная ссылка на Role из WorkCatalogContext. На данном этапе хранится как строка (UUID). Валидация существования Role при создании/обновлении User реализуется в будущем, когда будет реализован домен WorkCatalog.

---

## 5. Use Cases

### Command Use Cases

| Use Case | Описание | InputDto | Зависимости |
|---|---|---|---|
| `CreateUserUseCase` | Создание пользователя (UC-ID-01) | `name`, `email`, `systemRole`, `?groupId`, `?roleId` | `UserRepositoryInterface`, `GroupRepositoryInterface`, `PasswordHasherInterface` |
| `DeactivateUserUseCase` | Деактивация пользователя (UC-ID-04) | `userId` | `UserRepositoryInterface` |
| `DeleteUserUseCase` | Soft-delete пользователя (UC-ID-06) | `userId` | `UserRepositoryInterface`, `TicketExistenceCheckerInterface` |
| `AssignUserToGroupUseCase` | Назначение User в Group (UC-ID-03) | `userId`, `groupId` | `UserRepositoryInterface`, `GroupRepositoryInterface` |
| `ChangeUserGroupUseCase` | Изменение/снятие принадлежности к Group (UC-ID-07) | `userId`, `?groupId` | `UserRepositoryInterface`, `GroupRepositoryInterface` |
| `CreateGroupUseCase` | Создание группы (UC-ID-02) | `name`, `?description` | `GroupRepositoryInterface` |
| `DeleteGroupUseCase` | Soft-delete группы (UC-ID-08) | `groupId` | `GroupRepositoryInterface`, `UserRepositoryInterface` |

### Query Use Cases

| Use Case | Описание | InputDto | OutputDto | Зависимости |
|---|---|---|---|---|
| `ListUsersUseCase` | Список пользователей (UC-ID-05) | `?groupId`, `?roleId`, `?isActive`, `page`, `perPage` | `items[]`, `total`, `page`, `perPage` | `UserRepositoryInterface`, `ListUsersOutputPortInterface` |
| `ListGroupsUseCase` | Список групп | `page`, `perPage` | `items[]`, `total`, `page`, `perPage` | `GroupRepositoryInterface`, `ListGroupsOutputPortInterface` |

### Детали Use Cases

**CreateUserUseCase:**
1. Маппинг примитивов в Value Objects (`UserId::generate()`, `Email`, `SystemRole`)
2. Проверка уникальности email: `UserRepositoryInterface::existsByEmail()`; при дубликате -- `DuplicateEmailException`
3. Если указан `groupId` -- проверка существования Group и что она не удалена; при отсутствии -- `GroupNotFoundException`
4. Хэширование пароля через `PasswordHasherInterface` (генерация временного пароля или передача через отдельный механизм -- см. issues)
5. Вызов `User::create(id, name, email, passwordHash, systemRole, groupId, roleId)`
6. Публикация события `UserCreated`
7. Сохранение через `UserRepositoryInterface::save()`

**DeactivateUserUseCase:**
1. Поиск User по ID; при отсутствии -- `UserNotFoundException`
2. Проверка: User не помечен удалённым; при нарушении -- `EntityDeletedException`
3. Проверка: User активен (`isActive = true`); при нарушении -- `UserAlreadyDeactivatedException`
4. Вызов `User::deactivate()`
5. Публикация события `UserDeactivated`
6. Сохранение через `UserRepositoryInterface::save()`

**DeleteUserUseCase (Soft-delete):**
1. Поиск User по ID; при отсутствии -- `UserNotFoundException`
2. Проверка: User не помечен удалённым
3. Проверка: `TicketExistenceCheckerInterface::hasTicketsForUser()` -- если Ticket есть, выбросить `UserHasLinkedTicketsException` с предложением деактивации
4. Вызов `User::softDelete()`
5. Публикация события `UserDeleted`
6. Сохранение через `UserRepositoryInterface::save()`

**AssignUserToGroupUseCase:**
1. Поиск User по ID; при отсутствии -- `UserNotFoundException`
2. Проверка: User не помечен удалённым; при нарушении -- `EntityDeletedException`
3. Поиск Group по ID; при отсутствии -- `GroupNotFoundException`
4. Проверка: Group не помечена удалённой; при нарушении -- `EntityDeletedException`
5. Вызов `User::assignToGroup(groupId)`
6. Публикация события `UserAssignedToGroup`
7. Сохранение через `UserRepositoryInterface::save()`

**ChangeUserGroupUseCase:**
1. Поиск User по ID; при отсутствии -- `UserNotFoundException`
2. Проверка: User не помечен удалённым; при нарушении -- `EntityDeletedException`
3. Если указан `groupId`:
   - Поиск Group по ID; при отсутствии -- `GroupNotFoundException`
   - Проверка: Group не помечена удалённой; при нарушении -- `EntityDeletedException`
   - Вызов `User::assignToGroup(groupId)`
4. Если `groupId` не указан (снятие привязки):
   - Вызов `User::removeFromGroup()`
5. Сохранение через `UserRepositoryInterface::save()`

**CreateGroupUseCase:**
1. Проверка уникальности наименования: `GroupRepositoryInterface::existsByName()`; при дубликате -- `DuplicateGroupNameException`
2. Вызов `Group::create(GroupId::generate(), name, description)`
3. Публикация события `GroupCreated`
4. Сохранение через `GroupRepositoryInterface::save()`

**DeleteGroupUseCase (Soft-delete):**
1. Поиск Group по ID; при отсутствии -- `GroupNotFoundException`
2. Проверка: Group не помечена удалённой
3. Проверка: `UserRepositoryInterface::countActiveUsersByGroupId() === 0`; при нарушении -- `GroupHasActiveUsersException`
4. Вызов `Group::softDelete()`
5. Сохранение через `GroupRepositoryInterface::save()`

**ListUsersUseCase / ListGroupsUseCase:**
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
| `UserCreated` | `app/src/Identity/Domain/Event/` | `UserId $userId`, `string $name`, `string $email`, `SystemRole $systemRole` | `CreateUserUseCase` |
| `UserDeactivated` | `app/src/Identity/Domain/Event/` | `UserId $userId` | `DeactivateUserUseCase` |
| `UserDeleted` | `app/src/Identity/Domain/Event/` | `UserId $userId` | `DeleteUserUseCase` |
| `UserAssignedToGroup` | `app/src/Identity/Domain/Event/` | `UserId $userId`, `GroupId $groupId` | `AssignUserToGroupUseCase` |
| `GroupCreated` | `app/src/Identity/Domain/Event/` | `GroupId $groupId`, `string $name` | `CreateGroupUseCase` |

Все события -- `final readonly class`, содержат `DateTimeImmutable $occurredAt`.

**Примечание:** события создания (Created) содержат ключевые атрибуты сущности; события деактивации (Deactivated), удаления (Deleted) и назначения (AssignedToGroup) содержат только идентификаторы -- минимальный контракт. Подписчик при необходимости может запросить полные данные сущности через API.

Сущности используют трейт `RecordsDomainEvents` для накопления событий. Публикация событий -- через Symfony Event Dispatcher.

### 6.2 Аудит-логирование (НФТ-Б-06)

**Механизм:** Event Listener на доменные события, запись через Symfony Logger (Monolog).

**Расположение:** `app/src/Identity/Infrastructure/EventListener/AuditLogEventListener.php`

**Принцип работы:**
1. При публикации доменного события (UserCreated, UserDeactivated, UserDeleted, UserAssignedToGroup, GroupCreated) Event Listener перехватывает событие
2. Формирует структурированную запись аудит-лога
3. Записывает через `LoggerInterface` (Monolog)

**Состав записи аудит-лога:**
- Идентификатор пользователя, выполнившего операцию
- Тип действия: `create`, `deactivate`, `delete`, `assign_to_group`
- Тип сущности и её идентификатор
- Временная метка (UTC)
- Результат операции (`success`)

**Место хранения:** стандартный лог-файл Symfony (либо stdout). Отдельная таблица `audit_log` в базе данных **не создаётся**. Просмотр аудит-лога через API **не предусмотрен**.

**Зависимости:** `Psr\Log\LoggerInterface`, `Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface`.

---

## 7. Доменные исключения

| Исключение | Когда выбрасывается |
|---|---|
| `UserNotFoundException` | User не найден по ID |
| `GroupNotFoundException` | Group не найдена по ID |
| `DuplicateEmailException` | Попытка создать User с email, который уже существует в системе |
| `DuplicateGroupNameException` | Попытка создать Group с наименованием, которое уже существует |
| `UserHasLinkedTicketsException` | Попытка soft-delete User, к которому привязаны Ticket. Сообщение содержит предложение деактивации |
| `GroupHasActiveUsersException` | Попытка soft-delete Group, в которой состоят активные User |
| `UserAlreadyDeactivatedException` | Попытка деактивировать User, который уже деактивирован |
| `EntityDeletedException` | Попытка выполнить операцию над сущностью, помеченной удалённой (`deletedAt IS NOT NULL`). Сообщение: «Сущность удалена и не может быть изменена» |

Все исключения наследуют `\DomainException`.

---

## 8. Persistence (Doctrine)

### Doctrine Entities

#### `Persistence\Entity\User`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `name` | `string(255)` | NOT NULL |
| `email` | `string(255)` | NOT NULL, UNIQUE |
| `password_hash` | `string(255)` | NOT NULL |
| `system_role` | `string(20)` | NOT NULL, `enumType: SystemRole` |
| `group_id` | `guid` | NULLABLE, FK -> groups, INDEX |
| `role_id` | `guid` | NULLABLE, INDEX |
| `is_active` | `boolean` | NOT NULL, DEFAULT true |
| `deleted_at` | `datetime_immutable` | NULLABLE |
| `created_at` | `datetime_immutable` | NOT NULL |
| `updated_at` | `datetime_immutable` | NULLABLE |

**Индексы:**
- UNIQUE на `email` (с условием `deleted_at IS NULL` -- partial unique index, чтобы разрешить повторное использование email после soft-delete)
- На `deleted_at` (для фильтрации активных пользователей)
- На `group_id` (для подсчёта пользователей в группе)
- На `role_id` (для фильтрации по роли)
- На `is_active` (для фильтрации по статусу активности)

#### `Persistence\Entity\Group`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `name` | `string(255)` | NOT NULL, UNIQUE |
| `description` | `text` | NULLABLE |
| `deleted_at` | `datetime_immutable` | NULLABLE |
| `created_at` | `datetime_immutable` | NOT NULL |
| `updated_at` | `datetime_immutable` | NULLABLE |

**Индексы:**
- UNIQUE на `name` (с условием `deleted_at IS NULL` -- partial unique index)
- На `deleted_at` (для фильтрации активных групп)

### Doctrine Repositories

Каждый наследует `ServiceEntityRepository`, содержит:
- `save(Entity, flush)`, `remove(Entity, flush)`
- `UserRepository`: методы `findByEmail()`, `findByGroupId()`, `findByRoleId()`, `findByIsActive()`, `countActiveByGroupId()`, `hasTicketsForUser()` (прямой запрос к таблице `tickets`)
- `GroupRepository`: методы `findByName()`, `existsByName()`
- Все методы с пагинацией (QueryBuilder + `setFirstResult()`/`setMaxResults()`)
- Все репозитории: методы `findAll` фильтруют по `deleted_at IS NULL`

---

## 9. Infrastructure (HTTP-слой)

### API-маршруты

| Метод | Путь | Контроллер | Имя маршрута |
|---|---|---|---|
| POST | `/api/identity/users` | `CreateUserController` | `identity_create_user` |
| GET | `/api/identity/users` | `ListUsersController` | `identity_list_users` |
| POST | `/api/identity/users/{id}/deactivate` | `DeactivateUserController` | `identity_deactivate_user` |
| DELETE | `/api/identity/users/{id}` | `DeleteUserController` | `identity_delete_user` |
| PUT | `/api/identity/users/{id}/group` | `AssignUserToGroupController` | `identity_assign_user_to_group` |
| PATCH | `/api/identity/users/{id}/group` | `ChangeUserGroupController` | `identity_change_user_group` |
| POST | `/api/identity/groups` | `CreateGroupController` | `identity_create_group` |
| GET | `/api/identity/groups` | `ListGroupsController` | `identity_list_groups` |
| DELETE | `/api/identity/groups/{id}` | `DeleteGroupController` | `identity_delete_group` |

**Примечание к маршрутам:**
- `POST .../deactivate` -- выбран POST, так как деактивация является доменным действием, а не CRUD-операцией
- `PUT .../group` -- назначение в Group (UC-ID-03)
- `PATCH .../group` -- изменение/снятие привязки к Group (UC-ID-07), так как поддерживает передачу `null` для снятия привязки

### Request DTO с валидацией

**CreateUserRequestDto:**
- `name: string` -- `#[NotBlank]`, `#[Length(max: 255)]`
- `email: string` -- `#[NotBlank]`, `#[Email]`, `#[Length(max: 255)]`
- `systemRole: string` -- `#[NotBlank]`, `#[Choice(callback: [SystemRole::class, 'values'])]`
- `groupId: ?string` -- `#[Uuid]`
- `roleId: ?string` -- `#[Uuid]`

**AssignUserToGroupRequestDto:**
- `groupId: string` -- `#[NotBlank]`, `#[Uuid]`

**ChangeUserGroupRequestDto:**
- `groupId: ?string` -- `#[Uuid]`

**ListUsersRequestDto** (GET, query-параметры):
- `groupId: ?string` -- `#[Uuid]`
- `roleId: ?string` -- `#[Uuid]`
- `isActive: ?bool`
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

**CreateGroupRequestDto:**
- `name: string` -- `#[NotBlank]`, `#[Length(max: 255)]`
- `description: ?string` -- `#[Length(max: 1000)]`

**ListGroupsRequestDto** (GET, query-параметры):
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

### Response DTO

Каждый Response DTO содержит OpenAPI-атрибуты (`#[OA\Schema]`, `#[OA\Property]`).

**UserResponseDto:** `id`, `name`, `email`, `systemRole`, `systemRoleLabel`, `groupId`, `groupName`, `roleId`, `roleName`, `isActive`
**GroupResponseDto:** `id`, `name`, `description`

**Важно:** согласно НФТ-У-02, User List возвращает наименования связанных Group и Role (а не только идентификаторы) для построения UI без дополнительных запросов.

List Response DTO содержат `items[]`, `total`, `page`, `perPage`.

---

## 10. Зависимости между компонентами

### Граф зависимостей (направление: зависимый -> от чего зависит)

```
Domain Layer (нет внешних зависимостей):
  Entity -> ValueObject, Enum, Exception
  Repository Interface -> Entity, ValueObject

Application Layer (зависит от Domain):
  UseCase -> Repository Interface, Entity, ValueObject, Enum, Exception, Port
  InputDto -> (только примитивы, нет зависимостей)
  OutputDto -> (только примитивы, нет зависимостей)
  OutputPort -> OutputDto
  Port (TicketExistenceCheckerInterface, PasswordHasherInterface) -> ValueObject

Infrastructure Layer (зависит от Domain + Application):
  Controller -> UseCase, Transformer, Presenter, RequestDto
  Transformer -> RequestDto, InputDto
  Presenter -> OutputDto, OutputPort, ResponseDto
  ValueResolver -> AbstractJsonValueResolver/AbstractValueResolver, RequestDto
  DoctrineRepository -> Domain Repository Interface, Domain Entity, Domain VO, Persistence Entity, Persistence Repository
  SymfonyPasswordHasher -> PasswordHasherInterface
  AuditLogEventListener -> Domain Event, LoggerInterface, TokenStorageInterface

Persistence Layer (зависит от Doctrine):
  Doctrine Entity -> ORM Mapping, Domain Enum
  Doctrine Repository -> Doctrine Entity, ServiceEntityRepository
```

### Зависимости от существующей инфраструктуры

- `App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver` -- для POST/PUT-запросов
- `App\Shared\Infrastructure\ValueResolver\AbstractValueResolver` -- для GET-запросов (List)
- `App\Persistence\Entity\*` и `App\Persistence\Repository\*` -- Doctrine-слой
- Symfony Framework: Route, AbstractController, JsonResponse, Validator, EventDispatcher, Security, Logger (Monolog), PasswordHasher

### Межконтекстные зависимости

- **TimesheetContext -> Identity:** TimesheetContext использует `employeeId` (ссылка на User) для привязки Ticket к исполнителю
- **Identity -> Persistence:** при soft-delete User проверка наличия Ticket выполняется через прямой запрос к таблице `tickets` (Persistence)
- **Identity -> WorkCatalog:** опциональная ссылка `roleId` у User на Role из WorkCatalogContext. На данном этапе хранится как строка (UUID), валидация существования Role отложена до реализации WorkCatalog
- **WorkCatalog -> Identity:** при удалении Role WorkCatalogContext запрашивает IdentityContext для проверки, есть ли пользователи с данной `roleId`

---

## 11. Порядок реализации

### Этап 1: Domain Layer

**Шаг 1.1:** Enum
- `SystemRole`

**Шаг 1.2:** Value Objects
- `UserId`, `GroupId`, `Email`, `HashedPassword`

**Шаг 1.3:** Доменные исключения
- Все исключения из раздела 7

**Шаг 1.4:** Доменные события
- `UserCreated`, `UserDeactivated`, `UserDeleted`, `UserAssignedToGroup`, `GroupCreated`

**Шаг 1.5:** Domain Entities
- `User` (с `isActive`, `deletedAt`, методами `deactivate()`, `softDelete()`, `assignToGroup()`, `removeFromGroup()`)
- `Group` (с `deletedAt`, методом `softDelete()`)

**Шаг 1.6:** Интерфейсы репозиториев
- `UserRepositoryInterface` (с `existsByEmail`, `countActiveUsersByGroupId`)
- `GroupRepositoryInterface` (с `existsByName`)

**Шаг 1.7:** Порты приложения
- `TicketExistenceCheckerInterface`
- `PasswordHasherInterface`

### Этап 2: Persistence Layer

**Шаг 2.1:** Doctrine Entities
- `Persistence\Entity\User` (с `deleted_at`, `is_active`, `password_hash`), `Group` (с `deleted_at`)

**Шаг 2.2:** Doctrine Repositories
- `Persistence\Repository\UserRepository`, `GroupRepository`

**Шаг 2.3:** Миграции
- Генерация через `doctrine:migrations:diff`
- Создание partial unique index на `email` (WHERE `deleted_at IS NULL`)
- Создание partial unique index на `name` для Group (WHERE `deleted_at IS NULL`)

### Этап 3: Application Layer

**Шаг 3.1:** InputDto и OutputDto
- Все DTO из раздела 5

**Шаг 3.2:** OutputPort-интерфейсы
- `ListUsersOutputPortInterface`, `ListGroupsOutputPortInterface`

**Шаг 3.3:** Command Use Cases -- создание
- `CreateUserUseCase`, `CreateGroupUseCase`

**Шаг 3.4:** Command Use Cases -- деактивация/удаление
- `DeactivateUserUseCase`, `DeleteUserUseCase`, `DeleteGroupUseCase`

**Шаг 3.5:** Command Use Cases -- назначение в Group
- `AssignUserToGroupUseCase`, `ChangeUserGroupUseCase`

**Шаг 3.6:** Query Use Cases
- `ListUsersUseCase`, `ListGroupsUseCase`

### Этап 4: Infrastructure Layer

**Шаг 4.1:** Реализации доменных репозиториев
- `DoctrineUserRepository`, `DoctrineGroupRepository`

**Шаг 4.2:** Реализация портов
- `SymfonyPasswordHasher` (реализация `PasswordHasherInterface`)
- `TicketExistenceChecker` в `DoctrineUserRepository` (реализация `TicketExistenceCheckerInterface`)

**Шаг 4.3:** Request DTO
- Все Request DTO с атрибутами валидации

**Шаг 4.4:** Response DTO
- Все Response DTO с OpenAPI-атрибутами

**Шаг 4.5:** Value Resolvers
- По одному на каждый Request DTO

**Шаг 4.6:** Input Transformers
- По одному на каждый Use Case

**Шаг 4.7:** Presenters
- `HttpListUsersPresenter`, `HttpListGroupsPresenter`

**Шаг 4.8:** Контроллеры
- Все контроллеры из раздела 9

**Шаг 4.9:** Event Listener
- `AuditLogEventListener`

### Этап 5: Тестирование

**Шаг 5.1:** Юнит-тесты Domain Layer
**Шаг 5.2:** Юнит-тесты Use Cases
**Шаг 5.3:** Интеграционные тесты Persistence
**Шаг 5.4:** Интеграционные тесты API (end-to-end)

---

## 12. Тестирование

### Юнит-тесты Domain Layer

| Тест | Что проверяем |
|---|---|
| `UserTest` | Создание через `create()`, деактивация, soft-delete, назначение в Group, снятие привязки, валидация пустого `name` |
| `GroupTest` | Создание через `create()`, soft-delete, валидация пустого `name` |
| `EmailTest` | Валидация формата email, нормализация в нижний регистр, equals |
| `HashedPasswordTest` | Корректное хранение хэша |
| `UserIdTest` / `GroupIdTest` | Валидация UUID, generate, equals |
| `SystemRoleTest` | Все кейсы enum, getLabel |

### Юнит-тесты Use Cases

| Тест | Что проверяем |
|---|---|
| `CreateUserUseCaseTest` | Успешное создание; дубликат email; несуществующая Group; публикация события `UserCreated` |
| `DeactivateUserUseCaseTest` | Успешная деактивация; User не найден; User удалён; User уже деактивирован; публикация события `UserDeactivated` |
| `DeleteUserUseCaseTest` | Успешный soft-delete; User не найден; User с Ticket (предложение деактивации); публикация события `UserDeleted` |
| `AssignUserToGroupUseCaseTest` | Успешное назначение; User не найден; Group не найдена; User/Group удалены; публикация `UserAssignedToGroup` |
| `ChangeUserGroupUseCaseTest` | Смена Group; снятие привязки (null); User не найден; Group не найдена |
| `CreateGroupUseCaseTest` | Успешное создание; дубликат имени; публикация `GroupCreated` |
| `DeleteGroupUseCaseTest` | Успешный soft-delete; Group не найдена; Group с активными User |
| `ListUsersUseCaseTest` | Фильтрация по groupId, roleId, isActive; пагинация; пустой результат |
| `ListGroupsUseCaseTest` | Пагинация; пустой результат |

### Интеграционные тесты

| Тест | Что проверяем |
|---|---|
| `DoctrineUserRepositoryTest` | `save`, `findById`, `existsByEmail`, `countActiveUsersByGroupId`, `hasTicketsForUser`, `findAll` с фильтрами и пагинацией, partial unique index на email |
| `DoctrineGroupRepositoryTest` | `save`, `findById`, `existsByName`, `findAll` с пагинацией, partial unique index на name |

### Интеграционные тесты API

| Тест | Что проверяем |
|---|---|
| `CreateUserControllerTest` | HTTP 201 при успехе; HTTP 422 при невалидных данных; HTTP 409 при дубликате email; HTTP 403 без прав Admin |
| `DeactivateUserControllerTest` | HTTP 200 при успехе; HTTP 404 при отсутствии; HTTP 409 при уже деактивированном |
| `DeleteUserControllerTest` | HTTP 204 при успехе; HTTP 404 при отсутствии; HTTP 409 при наличии Ticket |
| `AssignUserToGroupControllerTest` | HTTP 200 при успехе; HTTP 404 при отсутствии User/Group |
| `ListUsersControllerTest` | HTTP 200; проверка фильтрации и пагинации; HTTP 403 для Employee |
| `CreateGroupControllerTest` | HTTP 201 при успехе; HTTP 409 при дубликате имени |
| `DeleteGroupControllerTest` | HTTP 204 при успехе; HTTP 409 при наличии активных User |
| `ListGroupsControllerTest` | HTTP 200; проверка пагинации |
