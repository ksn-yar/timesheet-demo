<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком отчётов и данными пагинации. */
#[OA\Schema(
    schema: 'ReportListResponse',
    description: 'Список отчётов с пагинацией.',
)]
final readonly class ReportListResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Элементы', description: 'Список отчётов на текущей странице.', type: 'array', items: new OA\Items(ref: ReportListItemResponseDto::class))]
        public array $items,
        #[OA\Property(title: 'Всего', description: 'Общее количество отчётов.', example: 42)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущий номер страницы.', example: 1)]
        public int $page,
        #[OA\Property(title: 'На странице', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
