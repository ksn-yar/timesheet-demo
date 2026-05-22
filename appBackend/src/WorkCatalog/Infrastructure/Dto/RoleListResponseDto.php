<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком ролей и метаданными пагинации. */
#[OA\Schema(
    schema: 'RoleListResponse',
    description: 'Список ролей с пагинацией.',
)]
final readonly class RoleListResponseDto
{
    /**
     * @param RoleResponseDto[] $items
     */
    public function __construct(
        /** @var RoleResponseDto[] */
        #[OA\Property(
            title: 'Роли',
            description: 'Список ролей.',
            type: 'array',
            items: new OA\Items(ref: RoleResponseDto::class),
        )]
        public array $items,
        #[OA\Property(title: 'Всего записей', description: 'Общее количество ролей.', example: 42)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущая страница.', example: 1)]
        public int $page,
        #[OA\Property(title: 'Размер страницы', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
