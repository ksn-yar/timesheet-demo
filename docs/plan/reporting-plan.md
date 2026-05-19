# План реализации домена Reporting

**Версия:** 1.1
**Дата:** 2026-03-22
**Основание:** docs/technical-specification-parts/reporting.md (v1.0)

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

Домен Reporting реализуется как отдельный Bounded Context `Reporting` в рамках существующей чистой архитектуры проекта. Reporting -- downstream-контекст: он потребляет данные из всех остальных доменов (Timesheet, Project Management, Work Catalog, Identity) и не изменяет их.

Структура следует принципам Clean Architecture и DDD:

- **Domain** -- сущности, Value Objects, Enum, интерфейсы репозиториев, доменные события, исключения
- **Application** -- Use Cases, InputDto, OutputDto, OutputPort-интерфейсы, порты к другим контекстам
- **Infrastructure** -- контроллеры, DTO запросов/ответов, Value Resolvers, Input Transformers, Presenters, реализации репозиториев, сервисы генерации файлов, Event Listener для аудит-логирования

Persistence-слой (Doctrine Entity и Repository) размещается в домене `Persistence` -- архитектурный компромисс проекта.

**Ключевая особенность домена:** Report -- это неизменяемый snapshot агрегированных данных. После создания Report не обновляется и не зависит от изменений в исходных данных (Ticket).

---

## 2. Структура модулей и файлов

### Bounded Context: Reporting

```
app/src/
├── Reporting/
│   ├── Domain/
│   │   ├── Entity/
│   │   │   └── Report.php
│   │   ├── ValueObject/
│   │   │   ├── ReportId.php
│   │   │   ├── ReportExportId.php
│   │   │   ├── ReportPeriod.php
│   │   │   ├── ReportFilters.php
│   │   │   ├── ReportGroupBy.php
│   │   │   ├── ReportData.php
│   │   │   └── ReportExport.php
│   │   ├── Enum/
│   │   │   ├── ExportFormat.php
│   │   │   └── GroupByDimension.php
│   │   ├── Repository/
│   │   │   ├── ReportRepositoryInterface.php
│   │   │   └── ReportExportRepositoryInterface.php
│   │   ├── Event/
│   │   │   ├── ReportAdded.php
│   │   │   └── ReportsExported.php
│   │   └── Exception/
│   │       ├── ReportNotFoundException.php
│   │       ├── ReportExportNotFoundException.php
│   │       ├── InvalidReportPeriodException.php
│   │       ├── EmptyGroupByException.php
│   │       └── ReportExportGenerationException.php
│   ├── Application/
│   │   ├── UseCase/
│   │   │   ├── CreateReportUseCase.php
│   │   │   ├── GetReportUseCase.php
│   │   │   ├── ListReportsUseCase.php
│   │   │   ├── ExportReportsUseCase.php
│   │   │   └── ListExportedReportsUseCase.php
│   │   ├── Dto/
│   │   │   ├── CreateReportInputDto.php
│   │   │   ├── GetReportInputDto.php
│   │   │   ├── GetReportOutputDto.php
│   │   │   ├── ListReportsInputDto.php
│   │   │   ├── ListReportsOutputDto.php
│   │   │   ├── ExportReportsInputDto.php
│   │   │   ├── ListExportedReportsInputDto.php
│   │   │   └── ListExportedReportsOutputDto.php
│   │   ├── Port/
│   │   │   ├── GetReportOutputPortInterface.php
│   │   │   ├── ListReportsOutputPortInterface.php
│   │   │   ├── ListExportedReportsOutputPortInterface.php
│   │   │   ├── TicketQueryServiceInterface.php
│   │   │   └── ReportFileGeneratorInterface.php
│   │   └── Service/
│   │       └── ReportAggregationService.php
│   └── Infrastructure/
│       ├── Controller/
│       │   ├── CreateReportController.php
│       │   ├── GetReportController.php
│       │   ├── ListReportsController.php
│       │   ├── ExportReportsController.php
│       │   ├── ListExportedReportsController.php
│       │   └── DownloadExportController.php
│       ├── Dto/
│       │   ├── CreateReportRequestDto.php
│       │   ├── ListReportsRequestDto.php
│       │   ├── ExportReportsRequestDto.php
│       │   ├── ListExportedReportsRequestDto.php
│       │   ├── ReportResponseDto.php
│       │   ├── ReportListItemResponseDto.php
│       │   ├── ReportListResponseDto.php
│       │   ├── ExportListItemResponseDto.php
│       │   └── ExportListResponseDto.php
│       ├── ValueResolver/
│       │   ├── CreateReportValueResolver.php
│       │   ├── ListReportsValueResolver.php
│       │   ├── ExportReportsValueResolver.php
│       │   └── ListExportedReportsValueResolver.php
│       ├── Transformer/
│       │   ├── CreateReportInputTransformer.php
│       │   ├── GetReportInputTransformer.php
│       │   ├── ListReportsInputTransformer.php
│       │   ├── ExportReportsInputTransformer.php
│       │   └── ListExportedReportsInputTransformer.php
│       ├── Presenter/
│       │   ├── HttpGetReportPresenter.php
│       │   ├── HttpListReportsPresenter.php
│       │   └── HttpListExportedReportsPresenter.php
│       ├── Service/
│       │   ├── DoctrineTicketQueryService.php
│       │   ├── CsvReportFileGenerator.php
│       │   ├── XlsxReportFileGenerator.php
│       │   ├── PdfReportFileGenerator.php
│       │   └── ReportFileGeneratorFactory.php
│       ├── Repository/
│       │   ├── ReportRepository.php
│       │   └── ReportExportRepository.php
│       └── EventListener/
│           └── AuditLogEventListener.php
```

### Persistence

```
app/src/
├── Persistence/
│   ├── Entity/
│   │   ├── Report.php
│   │   └── ReportExport.php
│   └── Repository/
│       ├── ReportRepository.php
│       └── ReportExportRepository.php
```

---

## 3. Сущности, Value Objects, Enum

### 3.1 Enum

#### ExportFormat

- **Расположение:** `app/src/Reporting/Domain/Enum/ExportFormat.php`
- **Тип:** `enum ExportFormat: string`
- **Кейсы:** `Csv = 'csv'`, `Xlsx = 'xlsx'`, `Pdf = 'pdf'`
- **Методы:** `getLabel(): string` (русскоязычные метки: "CSV", "XLSX", "PDF"), `getExtension(): string` (расширение файла: `.csv`, `.xlsx`, `.pdf`), `getContentType(): string` (MIME-тип)

#### GroupByDimension

- **Расположение:** `app/src/Reporting/Domain/Enum/GroupByDimension.php`
- **Тип:** `enum GroupByDimension: string`
- **Кейсы:** `Employee = 'employee'`, `Group = 'group'`, `Project = 'project'`, `ChangeRequest = 'cr'`, `Task = 'task'`, `Work = 'work'`
- **Методы:** `getLabel(): string` (русскоязычные метки: "Сотрудник", "Группа", "Проект", "CR", "Задача", "Вид работ")

**Расширяемость (НФТ-Р-03):** добавление нового измерения группировки -- это добавление нового кейса в Enum и адаптация логики агрегации в `ReportAggregationService`. Архитектура контекста при этом не требует переработки.

### 3.2 Value Objects

| Value Object | Расположение | Описание |
|---|---|---|
| `ReportId` | `app/src/Reporting/Domain/ValueObject/ReportId.php` | Типизированный UUID-идентификатор Report |
| `ReportExportId` | `app/src/Reporting/Domain/ValueObject/ReportExportId.php` | Типизированный UUID-идентификатор ReportExport (для хранения и скачивания) |
| `ReportPeriod` | `app/src/Reporting/Domain/ValueObject/ReportPeriod.php` | Период выборки: `from` (DateTimeImmutable) и `to` (DateTimeImmutable) с инвариантом `from <= to` |
| `ReportFilters` | `app/src/Reporting/Domain/ValueObject/ReportFilters.php` | Набор опциональных фильтров: `?employeeIds`, `?groupIds`, `?projectIds`, `?crIds`, `?taskIds`, `?workIds` (массивы UUID-строк) |
| `ReportGroupBy` | `app/src/Reporting/Domain/ValueObject/ReportGroupBy.php` | Набор измерений группировки: массив `GroupByDimension[]` с инвариантом "минимум одно измерение" |
| `ReportData` | `app/src/Reporting/Domain/ValueObject/ReportData.php` | Неизменяемый snapshot агрегированных данных (массив ассоциативных данных) |
| `ReportExport` | `app/src/Reporting/Domain/ValueObject/ReportExport.php` | Артефакт экспорта: `reportIds` (UUID[]), `format` (ExportFormat), `generatedAt` (DateTimeImmutable), `fileRef` (string) |

Каждый ID Value Object:
- Принимает `string $value` в конструкторе
- Валидирует формат UUID
- Предоставляет методы `value(): string` и `equals(self $other): bool`
- Имеет статический метод `generate(): self` для создания нового UUID

`ReportPeriod`:
- Принимает `DateTimeImmutable $from`, `DateTimeImmutable $to`
- Валидирует инвариант ИН-23: `from <= to`; при нарушении -- `InvalidReportPeriodException`
- Предоставляет методы `from(): DateTimeImmutable`, `to(): DateTimeImmutable`

`ReportFilters`:
- Принимает опциональные массивы UUID-строк для каждого фильтра
- Предоставляет getter-методы для каждого фильтра
- Метод `toArray(): array` для сериализации в JSON
- Статический метод `fromArray(array $data): self` для десериализации

`ReportGroupBy`:
- Принимает `GroupByDimension[] $dimensions`
- Валидирует: массив не пустой (бизнес-правило); при нарушении -- `EmptyGroupByException`
- Предоставляет метод `dimensions(): array`, `toArray(): array`, статический `fromArray(array $data): self`

`ReportData`:
- Принимает `array $rows` -- массив ассоциативных массивов с агрегированными данными
- Неизменяем после создания
- Предоставляет методы `rows(): array`, `toArray(): array`, статический `fromArray(array $data): self`

`ReportExport`:
- Принимает `ReportExportId $id`, `array $reportIds`, `ExportFormat $format`, `DateTimeImmutable $generatedAt`, `string $fileRef`
- Имеет `id` (`ReportExportId`) для обеспечения хранения, пагинации, фильтрации и скачивания файла по ID
- Неизменяем после создания
- Предоставляет getter-методы для всех атрибутов

### 3.3 Domain Entity (Aggregate Root)

#### Report

- **Расположение:** `app/src/Reporting/Domain/Entity/Report.php`
- **Атрибуты:** `id: ReportId`, `name: string`, `createdBy: string` (UUID-строка, ссылка на User), `createdAt: DateTimeImmutable`, `period: ReportPeriod`, `filters: ReportFilters`, `groupBy: ReportGroupBy`, `data: ReportData`
- **Фабричный метод `create()`:** валидирует, что `name` не пустой; устанавливает `createdAt` в текущее время. Принимает уже сагрегированные данные.
- **Фабричный метод `restore()`:** восстановление из хранилища без валидации
- **Инварианты (ИН-21, ИН-22, ИН-23):**
  - Все атрибуты неизменны после создания (нет setter-методов)
  - `name` не может быть пустым
  - `periodFrom <= periodTo` (гарантируется Value Object `ReportPeriod`)
  - `groupBy` содержит минимум одно измерение (гарантируется Value Object `ReportGroupBy`)

---

## 4. Доменные репозитории

### Интерфейсы (Domain)

| Интерфейс | Расположение | Ключевые методы |
|---|---|---|
| `ReportRepositoryInterface` | `app/src/Reporting/Domain/Repository/` | `save(Report)`, `findById(ReportId): ?Report`, `findAll(criteria): array`, `count(criteria): int` |
| `ReportExportRepositoryInterface` | `app/src/Reporting/Domain/Repository/` | `save(ReportExport)`, `findById(ReportExportId): ?ReportExport`, `findAll(criteria): array`, `count(criteria): int` |

**Примечания:**
- `findAll` поддерживает фильтрацию по: `createdBy`, `periodFrom`, `periodTo`, поиск по `name` (LIKE)
- `findAll` поддерживает пагинацию (`offset`, `limit`)
- `findAll` для Report List не загружает поле `data` (только мета-информация)
- `ReportExportRepositoryInterface::findAll` поддерживает фильтрацию по `format`, `generatedAt`

### Порты к другим контекстам (Application)

#### TicketQueryServiceInterface

- **Расположение:** `app/src/Reporting/Application/Port/TicketQueryServiceInterface.php`
- **Метод:** `queryTickets(ReportPeriod $period, ReportFilters $filters): array`
- **Описание:** запрашивает Ticket из Timesheet с учётом фильтров и периода. Возвращает массив данных Ticket (не доменные сущности Timesheet, а проекции -- массивы с нужными полями). Это Anti-Corruption Layer.
- **Реализация:** `DoctrineTicketQueryService` -- прямой запрос к таблице `tickets` через Doctrine DBAL/QueryBuilder. Это допустимо, т.к. все данные в одной базе (аналогично `TicketExistenceCheckerInterface` в Identity).

Возвращаемые данные Ticket (проекция):
- `ticketId`, `employeeId`, `employeeName`, `groupId`, `groupName`, `taskId`, `taskName`, `crId`, `crName`, `projectId`, `projectName`, `workId`, `workName`, `date`, `hours`, `rateSnapshot`

**Обоснование:** Reporting нужны данные из нескольких доменов (Employee.name, Group.name, Project.name, CR.name, Task.name, Work.name), поэтому `TicketQueryService` делает JOIN-запрос через Persistence, собирая все необходимые имена. Это не нарушает изоляцию контекстов, т.к. реализация живёт в Infrastructure, а интерфейс -- в Application.

#### ReportFileGeneratorInterface

- **Расположение:** `app/src/Reporting/Application/Port/ReportFileGeneratorInterface.php`
- **Метод:** `generate(array $reports, ExportFormat $format): string` (возвращает путь/ссылку на сгенерированный файл)
- **Описание:** генерирует файл выгрузки в указанном формате из данных переданных Report
- **Реализация:** `ReportFileGeneratorFactory` выбирает конкретный генератор (`CsvReportFileGenerator`, `XlsxReportFileGenerator`, `PdfReportFileGenerator`) на основе формата. Каждый генератор реализует общий интерфейс.

---

## 5. Use Cases

### Command Use Cases

| Use Case | Описание | InputDto | Зависимости |
|---|---|---|---|
| `CreateReportUseCase` | Формирование Report (UC-RP-01) | `reportId`, `name`, `createdBy`, `periodFrom`, `periodTo`, `?filters`, `groupBy[]` | `ReportRepositoryInterface`, `TicketQueryServiceInterface`, `ReportAggregationService` |
| `ExportReportsUseCase` | Выгрузка Report в файл (UC-RP-02) | `exportId`, `reportIds[]`, `format` | `ReportRepositoryInterface`, `ReportExportRepositoryInterface`, `ReportFileGeneratorInterface` |

**Примечания к Command Use Cases:**
- **Паттерн Command:** оба Command Use Case имеют `execute(): void` (без OutputPort/Presenter). Контроллер генерирует идентификатор заранее (`ReportId::generate()` / `ReportExportId::generate()`), передаёт его через InputDto, а после `execute()` возвращает HTTP 201 с идентификатором. Клиент при необходимости запрашивает полные данные через GET-эндпоинт.
- **`createdBy`:** поле `createdBy` (UUID автора) не передаётся клиентом в HTTP-запросе. Оно извлекается из токена аутентификации (Security Token) в `CreateReportInputTransformer` и передаётся в `CreateReportInputDto`. UseCase получает уже готовый UUID автора.
- **`reportId` / `exportId`:** идентификаторы генерируются в `InputTransformer` и передаются в InputDto как строки (UUID).

### Query Use Cases

| Use Case | Описание | InputDto | OutputDto | Зависимости |
|---|---|---|---|---|
| `GetReportUseCase` | Получение Report по ID | `reportId` | Полные данные Report с `data` | `ReportRepositoryInterface`, `GetReportOutputPortInterface` |
| `ListReportsUseCase` | Просмотр Report List (UC-RP-03) | `?createdBy`, `?periodFrom`, `?periodTo`, `?name`, `page`, `perPage` | Список Report без `data` | `ReportRepositoryInterface`, `ListReportsOutputPortInterface` |
| `ListExportedReportsUseCase` | Просмотр Exported Reports List (UC-RP-04) | `?format`, `?generatedAtFrom`, `?generatedAtTo`, `page`, `perPage` | Список ReportExport | `ReportExportRepositoryInterface`, `ListExportedReportsOutputPortInterface` |

### Детали Use Cases

**CreateReportUseCase:**
1. Маппинг примитивов в Value Objects: `new ReportId($input->reportId)`, `ReportPeriod`, `ReportFilters`, `ReportGroupBy`
2. Валидация `name` не пустой (дополнительно к инвариантам Value Object)
3. Запрос Ticket через `TicketQueryServiceInterface::queryTickets(period, filters)`
4. Агрегация данных через `ReportAggregationService::aggregate(tickets, groupBy)` -- суммирование `hours`, вычисление `rateSnapshot * hours` (стоимость), группировка по указанным измерениям
5. Создание `ReportData` из агрегированных данных
6. Вызов `Report::create(id, name, createdBy, period, filters, groupBy, data)`
7. Публикация события `ReportAdded`
8. Сохранение через `ReportRepositoryInterface::save()`

**ExportReportsUseCase:**
1. Маппинг `$input->exportId` в `ReportExportId`
2. Валидация: `reportIds` не пустой
3. Для каждого `reportId` -- поиск Report через `ReportRepositoryInterface::findById()`; при отсутствии -- `ReportNotFoundException`
4. Генерация файла через `ReportFileGeneratorInterface::generate(reports, format)`
5. Создание `ReportExport` Value Object с `id`, `generatedAt = now()`, `fileRef` = результат генерации
6. Сохранение `ReportExport` через `ReportExportRepositoryInterface::save()`
7. Публикация события `ReportsExported`

**GetReportUseCase:**
1. Маппинг `reportId` в `ReportId`
2. Поиск Report по ID; при отсутствии -- `ReportNotFoundException`
3. Маппинг в `GetReportOutputDto` (включая полные `data`)
4. Передача через `$this->presenter->present(outputDto)`

**ListReportsUseCase:**
1. Маппинг фильтров из InputDto
2. Запрос к репозиторию с фильтрацией и пагинацией (без загрузки `data`)
3. Маппинг в `ListReportsOutputDto`
4. Передача через `$this->presenter->present(outputDto)`

**ListExportedReportsUseCase:**
1. Маппинг фильтров из InputDto
2. Запрос к репозиторию с фильтрацией и пагинацией
3. Маппинг в `ListExportedReportsOutputDto`
4. Передача через `$this->presenter->present(outputDto)`

### Application Service

#### ReportAggregationService

- **Расположение:** `app/src/Reporting/Application/Service/ReportAggregationService.php`
- **Описание:** Stateless-сервис, выполняющий агрегацию Ticket-данных по заданным измерениям группировки
- **Метод:** `aggregate(array $tickets, ReportGroupBy $groupBy): ReportData`
- **Логика:**
  1. Группирует записи Ticket по комбинации указанных измерений (`groupBy`)
  2. Для каждой группы вычисляет:
     - `totalHours` -- сумма `hours`
     - `totalCost` -- сумма `hours * rateSnapshot`
     - `ticketCount` -- количество Ticket в группе
  3. Включает наименования измерений (employeeName, groupName, projectName и т.д.) для отображения в UI
  4. Возвращает `ReportData` с результатом агрегации

---

## 6. Доменные события и аудит-логирование

### 6.1 Доменные события

| Событие | Расположение | Поля | Триггер |
|---|---|---|---|
| `ReportAdded` | `app/src/Reporting/Domain/Event/` | `ReportId $reportId`, `string $createdBy`, `DateTimeImmutable $periodFrom`, `DateTimeImmutable $periodTo` | `CreateReportUseCase` |
| `ReportsExported` | `app/src/Reporting/Domain/Event/` | `array $reportIds`, `ExportFormat $format`, `string $fileRef`, `DateTimeImmutable $generatedAt` | `ExportReportsUseCase` |

Все события -- `final readonly class`, содержат `DateTimeImmutable $occurredAt`.

Сущность Report использует трейт `RecordsDomainEvents` для накопления событий. Публикация событий -- через Symfony Event Dispatcher.

### 6.2 Аудит-логирование (НФТ-Б-06)

**Механизм:** Event Listener на доменные события, запись через Symfony Logger (Monolog).

**Расположение:** `app/src/Reporting/Infrastructure/EventListener/AuditLogEventListener.php`

**Принцип работы:**
1. При публикации доменного события (ReportAdded, ReportsExported) Event Listener перехватывает событие
2. Формирует структурированную запись аудит-лога
3. Записывает через `LoggerInterface` (Monolog)

**Состав записи аудит-лога:**
- Идентификатор пользователя, выполнившего операцию
- Тип действия: `create_report`, `export_reports`
- Тип сущности и её идентификатор (или идентификаторы)
- Временная метка (UTC)
- Результат операции (`success`)

---

## 7. Доменные исключения

| Исключение | Когда выбрасывается |
|---|---|
| `ReportNotFoundException` | Report не найден по ID (GetReportUseCase, ExportReportsUseCase) |
| `ReportExportNotFoundException` | ReportExport не найден по ID (DownloadExportController) |
| `InvalidReportPeriodException` | `periodFrom > periodTo` (Value Object ReportPeriod) |
| `EmptyGroupByException` | `groupBy` не содержит ни одного измерения (Value Object ReportGroupBy) |
| `ReportExportGenerationException` | Ошибка при генерации файла выгрузки (CsvReportFileGenerator и др.) |

Все исключения наследуют `\DomainException`.

---

## 8. Persistence (Doctrine)

### Doctrine Entities

#### `Persistence\Entity\Report`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `name` | `string(255)` | NOT NULL |
| `created_by` | `guid` | NOT NULL, INDEX |
| `created_at` | `datetime_immutable` | NOT NULL |
| `period_from` | `date_immutable` | NOT NULL |
| `period_to` | `date_immutable` | NOT NULL |
| `filters` | `json` | NULLABLE |
| `group_by` | `json` | NOT NULL |
| `data` | `json` | NOT NULL |

**Индексы:**
- На `created_by` (для фильтрации по автору)
- На `period_from`, `period_to` (для фильтрации по периоду)
- На `created_at` (для сортировки по дате создания)
- Полнотекстовый или обычный индекс на `name` (для поиска по наименованию)

#### `Persistence\Entity\ReportExport`

| Колонка | Тип | Ограничения |
|---|---|---|
| `id` | `guid` | PK, `gen_random_uuid()` |
| `report_ids` | `json` | NOT NULL |
| `format` | `string(10)` | NOT NULL, `enumType: ExportFormat` |
| `generated_at` | `datetime_immutable` | NOT NULL |
| `file_ref` | `string(1024)` | NOT NULL |

**Индексы:**
- На `format` (для фильтрации по формату)
- На `generated_at` (для фильтрации и сортировки по дате)

**Примечание:** ReportExport в ТЗ описан как Value Object, но для хранения списка выгрузок (Exported Reports List) и обеспечения пагинации он моделируется как отдельная Doctrine Entity в Persistence-слое. В доменном слое он остаётся Value Object, т.к. не имеет жизненного цикла и не изменяется после создания.

### Doctrine Repositories

Каждый наследует `ServiceEntityRepository`, содержит:
- `save(Entity, flush)`
- `ReportRepository`: методы `findById()`, `findAllMeta()` (без `data`), `countByCriteria()`
- `ReportExportRepository`: методы `findById()`, `findAllByCriteria()`, `countByCriteria()`
- Все методы с пагинацией (QueryBuilder + `setFirstResult()`/`setMaxResults()`)

**Оптимизация Report List (НФТ-П-05):**
- `findAllMeta()` использует частичный SELECT (без колонки `data`) для экономии памяти
- Пагинация ограничивает выборку до максимума 1 000 записей за запрос

---

## 9. Infrastructure (HTTP-слой)

### API-маршруты

| Метод | Путь | Контроллер | Имя маршрута |
|---|---|---|---|
| POST | `/reporting/reports` | `CreateReportController` | `reporting_create_report` |
| GET | `/reporting/reports` | `ListReportsController` | `reporting_list_reports` |
| GET | `/reporting/reports/{id}` | `GetReportController` | `reporting_get_report` |
| POST | `/reporting/exports` | `ExportReportsController` | `reporting_export_reports` |
| GET | `/reporting/exports` | `ListExportedReportsController` | `reporting_list_exported_reports` |
| GET | `/reporting/exports/{id}/download` | `DownloadExportController` | `reporting_download_export` |

**Примечания к маршрутам:**
- `POST /reporting/reports` -- создание Report, т.к. это ресурсоёмкая операция формирования snapshot
- `POST /reporting/exports` -- генерация файла экспорта
- `GET .../exports/{id}/download` -- скачивание файла (возвращает BinaryFileResponse)

### Request DTO с валидацией

**CreateReportRequestDto:**
- `name: string` -- `#[NotBlank]`, `#[Length(max: 255)]`
- `periodFrom: string` -- `#[NotBlank]`, `#[Date]`
- `periodTo: string` -- `#[NotBlank]`, `#[Date]`
- `filters: ?array` -- опциональный объект фильтров
- `filters.employeeIds: ?string[]` -- `#[All([#[Uuid]])]`
- `filters.groupIds: ?string[]` -- `#[All([#[Uuid]])]`
- `filters.projectIds: ?string[]` -- `#[All([#[Uuid]])]`
- `filters.crIds: ?string[]` -- `#[All([#[Uuid]])]`
- `filters.taskIds: ?string[]` -- `#[All([#[Uuid]])]`
- `filters.workIds: ?string[]` -- `#[All([#[Uuid]])]`
- `groupBy: string[]` -- `#[NotBlank]`, `#[Count(min: 1)]`, `#[All([#[Choice(callback: [GroupByDimension::class, 'values'])]])]`

**ListReportsRequestDto** (GET, query-параметры):
- `createdBy: ?string` -- `#[Uuid]`
- `periodFrom: ?string` -- `#[Date]`
- `periodTo: ?string` -- `#[Date]`
- `name: ?string` -- `#[Length(max: 255)]`
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

**ExportReportsRequestDto:**
- `reportIds: string[]` -- `#[NotBlank]`, `#[Count(min: 1)]`, `#[All([#[Uuid]])]`
- `format: string` -- `#[NotBlank]`, `#[Choice(callback: [ExportFormat::class, 'values'])]`

**ListExportedReportsRequestDto** (GET, query-параметры):
- `format: ?string` -- `#[Choice(callback: [ExportFormat::class, 'values'])]`
- `generatedAtFrom: ?string` -- `#[DateTime]`
- `generatedAtTo: ?string` -- `#[DateTime]`
- `page: int = 1` -- `#[Positive]`
- `perPage: int = 20` -- `#[Range(min: 1, max: 100)]`

### Response DTO

Каждый Response DTO содержит OpenAPI-атрибуты (`#[OA\Schema]`, `#[OA\Property]`).

**ReportResponseDto:** `id`, `name`, `createdBy`, `createdByName`, `createdAt`, `periodFrom`, `periodTo`, `filters`, `groupBy`, `data`

**ReportListItemResponseDto:** `id`, `name`, `createdBy`, `createdByName`, `createdAt`, `periodFrom`, `periodTo` (без `data`, `filters`, `groupBy`)

**ExportListItemResponseDto:** `id`, `reportIds`, `format`, `generatedAt`, `fileRef`

**Важно (НФТ-У-02):** Report List возвращает имя автора (`createdByName`), а не только `createdBy` (UUID), для построения UI без дополнительных запросов.

List Response DTO содержат `items[]`, `total`, `page`, `perPage`.

---

## 10. Зависимости между компонентами

### Граф зависимостей (направление: зависимый -> от чего зависит)

```
Domain Layer (нет внешних зависимостей):
  Entity -> ValueObject, Enum, Exception
  Repository Interface -> Entity, ValueObject

Application Layer (зависит от Domain):
  UseCase -> Repository Interface, Entity, ValueObject, Enum, Exception, Port, Service
  InputDto -> (только примитивы, нет зависимостей)
  OutputDto -> (только примитивы, нет зависимостей)
  OutputPort -> OutputDto
  Port (TicketQueryServiceInterface, ReportFileGeneratorInterface) -> ValueObject, Enum
  Service (ReportAggregationService) -> ValueObject

Infrastructure Layer (зависит от Domain + Application):
  Controller (Query) -> UseCase, Transformer, Presenter, RequestDto
  Controller (Command) -> UseCase, Transformer, RequestDto
  Transformer -> RequestDto, InputDto
  Presenter -> OutputDto, OutputPort, ResponseDto
  ValueResolver -> AbstractJsonValueResolver/AbstractValueResolver, RequestDto
  ReportRepository -> Domain Repository Interface, Domain Entity, Domain VO, Persistence Entity, Persistence Repository
  DoctrineTicketQueryService -> TicketQueryServiceInterface, Doctrine DBAL
  CsvReportFileGenerator -> ReportFileGeneratorInterface
  XlsxReportFileGenerator -> ReportFileGeneratorInterface
  PdfReportFileGenerator -> ReportFileGeneratorInterface
  ReportFileGeneratorFactory -> ReportFileGeneratorInterface, ExportFormat
  AuditLogEventListener -> Domain Event, LoggerInterface, TokenStorageInterface

Persistence Layer (зависит от Doctrine):
  Doctrine Entity -> ORM Mapping, Domain Enum
  Doctrine Repository -> Doctrine Entity, ServiceEntityRepository
```

### Зависимости от существующей инфраструктуры

- `App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver` -- для POST-запросов
- `App\Shared\Infrastructure\ValueResolver\AbstractValueResolver` -- для GET-запросов (List)
- `App\Persistence\Entity\*` и `App\Persistence\Repository\*` -- Doctrine-слой
- Symfony Framework: Route, AbstractController, JsonResponse, BinaryFileResponse, Validator, EventDispatcher, Security, Logger (Monolog)

### Межконтекстные зависимости

- **Reporting -> Timesheet (Persistence):** прямой запрос к таблице `tickets` + JOIN с таблицами `users`, `groups`, `tasks`, `change_requests`, `projects`, `works` для получения данных и наименований
- **Reporting -> Identity (Persistence):** JOIN к таблице `users` для получения имени автора Report (`createdByName`) в Report List
- **Reporting не является upstream:** ни один домен не зависит от Reporting

### Внешние библиотеки для генерации файлов

| Формат | Библиотека | Обоснование |
|---|---|---|
| CSV | Встроенные функции PHP (`fputcsv`) | Нет необходимости во внешних зависимостях |
| XLSX | `phpoffice/phpspreadsheet` | Стандартная библиотека для работы с Excel в PHP |
| PDF | `dompdf/dompdf` или `tecnickcom/tcpdf` | Генерация PDF из HTML-шаблона (dompdf) или программно (tcpdf) |

---

## 11. Порядок реализации

### Этап 1: Domain Layer

**Шаг 1.1:** Enum
- `ExportFormat`, `GroupByDimension`

**Шаг 1.2:** Value Objects
- `ReportId`, `ReportExportId`, `ReportPeriod`, `ReportFilters`, `ReportGroupBy`, `ReportData`, `ReportExport`

**Шаг 1.3:** Доменные исключения
- `ReportNotFoundException`, `ReportExportNotFoundException`, `InvalidReportPeriodException`, `EmptyGroupByException`, `ReportExportGenerationException`

**Шаг 1.4:** Доменные события
- `ReportAdded`, `ReportsExported`

**Шаг 1.5:** Domain Entity
- `Report` (immutable Aggregate Root: `create()`, `restore()`, без setter-методов)

**Шаг 1.6:** Интерфейсы репозиториев
- `ReportRepositoryInterface`, `ReportExportRepositoryInterface`

### Этап 2: Persistence Layer

**Шаг 2.1:** Doctrine Entities
- `Persistence\Entity\Report`, `Persistence\Entity\ReportExport`

**Шаг 2.2:** Doctrine Repositories
- `Persistence\Repository\ReportRepository`, `ReportExportRepository`

**Шаг 2.3:** Миграции
- Генерация через `doctrine:migrations:diff`
- Создание индексов на `created_by`, `period_from`/`period_to`, `created_at`, `name`, `format`, `generated_at`

### Этап 3: Application Layer

**Шаг 3.1:** InputDto и OutputDto
- Все DTO из раздела 5

**Шаг 3.2:** OutputPort-интерфейсы
- `GetReportOutputPortInterface`, `ListReportsOutputPortInterface`, `ListExportedReportsOutputPortInterface`

**Шаг 3.3:** Порты к другим контекстам
- `TicketQueryServiceInterface`, `ReportFileGeneratorInterface`

**Шаг 3.4:** Application Service
- `ReportAggregationService`

**Шаг 3.5:** Command Use Cases
- `CreateReportUseCase`, `ExportReportsUseCase`

**Шаг 3.6:** Query Use Cases
- `GetReportUseCase`, `ListReportsUseCase`, `ListExportedReportsUseCase`

### Этап 4: Infrastructure Layer

**Шаг 4.1:** Реализации доменных репозиториев
- `ReportRepository`, `ReportExportRepository`

**Шаг 4.2:** Реализация портов
- `DoctrineTicketQueryService` (прямой запрос к таблицам Ticket, User, Group, Task, CR, Project, Work)

**Шаг 4.3:** Генераторы файлов
- `CsvReportFileGenerator`, `XlsxReportFileGenerator`, `PdfReportFileGenerator`, `ReportFileGeneratorFactory`

**Шаг 4.4:** Request DTO
- Все Request DTO с атрибутами валидации

**Шаг 4.5:** Response DTO
- Все Response DTO с OpenAPI-атрибутами

**Шаг 4.6:** Value Resolvers
- По одному на каждый Request DTO

**Шаг 4.7:** Input Transformers
- По одному на каждый Use Case

**Шаг 4.8:** Presenters (только для Query Use Cases)
- `HttpGetReportPresenter`, `HttpListReportsPresenter`, `HttpListExportedReportsPresenter`

**Шаг 4.9:** Контроллеры
- Все контроллеры из раздела 9

**Шаг 4.10:** Event Listener
- `AuditLogEventListener`

**Шаг 4.11:** Установка внешних библиотек
- `composer require phpoffice/phpspreadsheet`
- `composer require dompdf/dompdf` (или `tecnickcom/tcpdf`)

### Этап 5: Тестирование

**Шаг 5.1:** Юнит-тесты Domain Layer
**Шаг 5.2:** Юнит-тесты Application Service и Use Cases
**Шаг 5.3:** Интеграционные тесты Persistence
**Шаг 5.4:** Интеграционные тесты API (end-to-end)

---

## 12. Тестирование

### Юнит-тесты Domain Layer

| Тест | Что проверяем |
|---|---|
| `ReportTest` | Создание через `create()`; неизменяемость (отсутствие setter-методов); валидация пустого `name` |
| `ReportPeriodTest` | Валидация `from <= to`; корректное создание при `from == to`; исключение при `from > to` |
| `ReportFiltersTest` | Создание с различными комбинациями фильтров; сериализация/десериализация через `toArray`/`fromArray` |
| `ReportGroupByTest` | Валидация минимум одного измерения; исключение при пустом массиве; сериализация/десериализация |
| `ReportDataTest` | Создание; неизменяемость; сериализация/десериализация |
| `ReportIdTest` | Валидация UUID, generate, equals |
| `ReportExportIdTest` | Валидация UUID, generate, equals |
| `ExportFormatTest` | Все кейсы enum, getLabel, getExtension, getContentType |
| `GroupByDimensionTest` | Все кейсы enum, getLabel |

### Юнит-тесты Use Cases и Application Service

| Тест | Что проверяем |
|---|---|
| `CreateReportUseCaseTest` | Успешное создание; пустой `name`; `periodFrom > periodTo`; пустой `groupBy`; пустой результат Ticket (Report с пустыми данными); публикация `ReportAdded` |
| `GetReportUseCaseTest` | Успешное получение; Report не найден |
| `ListReportsUseCaseTest` | Фильтрация по createdBy, периоду, name; пагинация; пустой результат |
| `ExportReportsUseCaseTest` | Успешный экспорт с переданным `exportId`; пустой `reportIds`; несуществующий Report; публикация `ReportsExported`; ошибка генерации файла |
| `ListExportedReportsUseCaseTest` | Фильтрация по format, generatedAt; пагинация; пустой результат |
| `ReportAggregationServiceTest` | Агрегация по одному измерению; по нескольким измерениям; пустой набор Ticket; корректность суммирования hours и cost |

### Интеграционные тесты

| Тест | Что проверяем |
|---|---|
| `ReportRepositoryTest` | `save`, `findById`, `findAllMeta` с фильтрами и пагинацией, `count`; проверка что `findAllMeta` не загружает `data` |
| `ReportExportRepositoryTest` | `save`, `findById`, `findAll` с фильтрами и пагинацией, `count` |
| `DoctrineTicketQueryServiceTest` | Корректность JOIN-запроса; фильтрация по периоду и фильтрам; возврат наименований связанных сущностей |

### Интеграционные тесты API

| Тест | Что проверяем |
|---|---|
| `CreateReportControllerTest` | HTTP 201 при успехе; HTTP 422 при невалидных данных; HTTP 422 при `periodFrom > periodTo`; HTTP 403 для Employee |
| `GetReportControllerTest` | HTTP 200 при успехе; HTTP 404 при отсутствии; HTTP 403 для Employee; проверка наличия `data` в ответе |
| `ListReportsControllerTest` | HTTP 200; проверка фильтрации и пагинации; проверка отсутствия `data` в элементах списка; HTTP 403 для Employee |
| `ExportReportsControllerTest` | HTTP 201 при успехе; HTTP 404 при несуществующем Report; HTTP 422 при невалидном формате; HTTP 403 для Employee |
| `ListExportedReportsControllerTest` | HTTP 200; проверка фильтрации и пагинации; HTTP 403 для Employee |
| `DownloadExportControllerTest` | HTTP 200 с корректным Content-Type; HTTP 404 при несуществующем файле |
