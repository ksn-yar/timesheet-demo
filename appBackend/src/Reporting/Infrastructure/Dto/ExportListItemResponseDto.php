<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO элемента списка выгрузок отчётов. */
#[OA\Schema(
    schema: 'ExportListItemResponse',
    description: 'Элемент списка выгрузок отчётов.',
)]
final readonly class ExportListItemResponseDto
{
    /** @param string[] $reportIds */
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор выгрузки.', format: 'uuid', example: '770e8400-e29b-41d4-a716-446655440000')]
        public string $id,
        #[OA\Property(title: 'Идентификаторы отчётов', description: 'UUID отчётов, включённых в выгрузку.', example: ['550e8400-e29b-41d4-a716-446655440000'])]
        public array $reportIds,
        #[OA\Property(title: 'Формат', description: 'Формат файла выгрузки.', example: 'csv')]
        public string $format,
        #[OA\Property(title: 'Дата генерации', description: 'Дата и время генерации файла в формате ISO 8601.', example: '2026-01-15T10:30:00+00:00')]
        public string $generatedAt,
        #[OA\Property(title: 'Ссылка на файл', description: 'Относительный путь к сгенерированному файлу.', example: 'var/exports/export_770e8400.csv')]
        public string $fileRef,
    ) {}
}
