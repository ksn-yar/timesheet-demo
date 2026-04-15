<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с полными данными отчёта, включая строки агрегации. */
#[OA\Schema(
    schema: 'ReportResponse',
    description: 'Полные данные отчёта.',
)]
final readonly class ReportResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор отчёта.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
        public string $id,
        #[OA\Property(title: 'Название', description: 'Название отчёта.', example: 'Отчёт за январь 2026')]
        public string $name,
        #[OA\Property(title: 'Создан пользователем', description: 'UUID пользователя, создавшего отчёт.', format: 'uuid', example: '660e8400-e29b-41d4-a716-446655440000')]
        public string $createdBy,
        #[OA\Property(title: 'Дата создания', description: 'Дата и время создания отчёта в формате ISO 8601.', example: '2026-01-15T10:30:00+00:00')]
        public string $createdAt,
        #[OA\Property(title: 'Начало периода', description: 'Дата начала отчётного периода.', example: '2026-01-01')]
        public string $periodFrom,
        #[OA\Property(title: 'Конец периода', description: 'Дата окончания отчётного периода.', example: '2026-01-31')]
        public string $periodTo,
        #[OA\Property(title: 'Фильтры', description: 'Применённые фильтры отчёта.', nullable: true)]
        public ?array $filters,
        #[OA\Property(title: 'Группировка', description: 'Измерения группировки данных.', example: ['employee', 'project'])]
        public array $groupBy,
        #[OA\Property(title: 'Данные', description: 'Строки агрегированных данных отчёта.')]
        public array $data,
    ) {}
}
