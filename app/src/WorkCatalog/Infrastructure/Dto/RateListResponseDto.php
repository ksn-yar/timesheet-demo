<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком ставок и метаданными пагинации. */
#[OA\Schema(
    schema: 'RateListResponse',
    description: 'Список ставок с пагинацией.',
)]
final readonly class RateListResponseDto
{
    /**
     * @param RateResponseDto[] $items
     */
    public function __construct(
        /** @var RateResponseDto[] */
        #[OA\Property(
            title: 'Ставки',
            description: 'Список ставок.',
            type: 'array',
            items: new OA\Items(ref: RateResponseDto::class),
        )]
        public array $items,
        #[OA\Property(title: 'Всего записей', description: 'Общее количество ставок.', example: 42)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущая страница.', example: 1)]
        public int $page,
        #[OA\Property(title: 'Размер страницы', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
