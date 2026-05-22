<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком видов работ и метаданными пагинации. */
#[OA\Schema(
    schema: 'WorkListResponse',
    description: 'Список видов работ с пагинацией.',
)]
final readonly class WorkListResponseDto
{
    /**
     * @param WorkResponseDto[] $items
     */
    public function __construct(
        /** @var WorkResponseDto[] */
        #[OA\Property(
            title: 'Виды работ',
            description: 'Список видов работ.',
            type: 'array',
            items: new OA\Items(ref: WorkResponseDto::class),
        )]
        public array $items,
        #[OA\Property(title: 'Всего записей', description: 'Общее количество видов работ.', example: 42)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущая страница.', example: 1)]
        public int $page,
        #[OA\Property(title: 'Размер страницы', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
