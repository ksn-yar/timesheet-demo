<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком выгрузок отчётов и данными пагинации. */
#[OA\Schema(
    schema: 'ExportListResponse',
    description: 'Список выгрузок отчётов с пагинацией.',
)]
final readonly class ExportListResponseDto
{
    /** @param ExportListItemResponseDto[] $items */
    public function __construct(
        #[OA\Property(title: 'Элементы', description: 'Список выгрузок на текущей странице.', type: 'array', items: new OA\Items(ref: ExportListItemResponseDto::class))]
        public array $items,
        #[OA\Property(title: 'Всего', description: 'Общее количество выгрузок.', example: 10)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущий номер страницы.', example: 1)]
        public int $page,
        #[OA\Property(title: 'На странице', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
