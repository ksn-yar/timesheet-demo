<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными тикета. */
#[OA\Schema(
    schema: 'TicketResponse',
    description: 'Данные тикета учёта времени.',
)]
final readonly class TicketResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор тикета.', format: 'uuid')]
        public string $id,
        #[OA\Property(title: 'ID сотрудника', description: 'Идентификатор сотрудника.', format: 'uuid')]
        public string $employeeId,
        #[OA\Property(title: 'Имя сотрудника', description: 'ФИО сотрудника.')]
        public string $employeeName,
        #[OA\Property(title: 'ID задачи', description: 'Идентификатор задачи.', format: 'uuid')]
        public string $taskId,
        #[OA\Property(title: 'Название задачи', description: 'Название задачи.')]
        public string $taskName,
        #[OA\Property(title: 'ID вида работ', description: 'Идентификатор вида работ.', format: 'uuid')]
        public string $workId,
        #[OA\Property(title: 'Название вида работ', description: 'Название вида работ.')]
        public string $workName,
        #[OA\Property(title: 'Дата', description: 'Дата тикета в формате YYYY-MM-DD.', format: 'date')]
        public string $date,
        #[OA\Property(title: 'Часы', description: 'Количество часов.')]
        public string $hours,
        #[OA\Property(title: 'Комментарий', description: 'Комментарий к тикету.', nullable: true)]
        public ?string $comment,
        #[OA\Property(title: 'Снимок ставки', description: 'Ставка на момент создания.')]
        public string $rateSnapshot,
        #[OA\Property(title: 'Тип', description: 'Тип тикета (manual/imported).')]
        public string $type,
        #[OA\Property(title: 'Источник импорта', description: 'Система-источник импорта.', nullable: true)]
        public ?string $importSource,
        #[OA\Property(title: 'Внешний ID', description: 'Внешний идентификатор.', nullable: true)]
        public ?string $externalId,
        #[OA\Property(title: 'Редактируемый', description: 'Разрешено ли редактирование.')]
        public bool $isEditable,
    ) {}
}
