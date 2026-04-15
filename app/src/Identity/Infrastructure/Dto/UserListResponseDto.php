<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком пользователей и метаданными пагинации. */
#[OA\Schema(
    schema: 'UserListResponse',
    description: 'Список пользователей с пагинацией.',
)]
final readonly class UserListResponseDto
{
    /**
     * @param UserResponseDto[] $items
     */
    public function __construct(
        /** @var UserResponseDto[] */
        #[OA\Property(
            title: 'Пользователи',
            description: 'Список пользователей.',
            type: 'array',
            items: new OA\Items(ref: UserResponseDto::class),
        )]
        public array $items,
        #[OA\Property(title: 'Всего записей', description: 'Общее количество пользователей.', example: 42)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущая страница.', example: 1)]
        public int $page,
        #[OA\Property(title: 'Размер страницы', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
