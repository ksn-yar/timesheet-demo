<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком запросов на изменение и метаданными пагинации. */
#[OA\Schema(
    schema: 'ChangeRequestListResponse',
    description: 'Список запросов на изменение с пагинацией.',
)]
final readonly class ChangeRequestListResponseDto
{
    /**
     * @param ChangeRequestResponseDto[] $items
     */
    public function __construct(
        /** @var ChangeRequestResponseDto[] */
        #[OA\Property(
            title: 'Запросы на изменение',
            description: 'Список запросов на изменение.',
            type: 'array',
            items: new OA\Items(ref: ChangeRequestResponseDto::class),
        )]
        public array $items,
        #[OA\Property(title: 'Всего записей', description: 'Общее количество запросов на изменение.', example: 42)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущая страница.', example: 1)]
        public int $page,
        #[OA\Property(title: 'Размер страницы', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
