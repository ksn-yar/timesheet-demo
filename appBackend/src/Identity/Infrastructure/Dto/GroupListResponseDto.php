<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком групп и метаданными пагинации. */
#[OA\Schema(
    schema: 'GroupListResponse',
    description: 'Список групп с пагинацией.',
)]
final readonly class GroupListResponseDto
{
    /**
     * @param GroupResponseDto[] $items
     */
    public function __construct(
        /** @var GroupResponseDto[] */
        #[OA\Property(
            title: 'Группы',
            description: 'Список групп.',
            type: 'array',
            items: new OA\Items(ref: GroupResponseDto::class),
        )]
        public array $items,
        #[OA\Property(title: 'Всего записей', description: 'Общее количество групп.', example: 42)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущая страница.', example: 1)]
        public int $page,
        #[OA\Property(title: 'Размер страницы', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
