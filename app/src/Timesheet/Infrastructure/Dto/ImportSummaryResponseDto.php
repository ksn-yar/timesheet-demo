<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с результатами импорта. */
#[OA\Schema(
    schema: 'ImportSummaryResponse',
    description: 'Результат выполнения импорта тикетов.',
)]
final readonly class ImportSummaryResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Импортировано', description: 'Количество успешно импортированных записей.')]
        public int $imported,
        #[OA\Property(title: 'Дубликаты', description: 'Количество пропущенных дубликатов.')]
        public int $duplicates,
        #[OA\Property(title: 'Ошибки', description: 'Количество записей с ошибками.')]
        public int $errors,
        #[OA\Property(title: 'Лог записи', description: 'Детализированный лог импорта.', type: 'array', items: new OA\Items(type: 'object'))]
        public array $logEntries,
    ) {}
}
