<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком клиентов и метаданными пагинации. */
#[OA\Schema(
    schema: 'ClientListResponse',
    description: 'Список клиентов с пагинацией.',
)]
final readonly class ClientListResponseDto
{
    /**
     * @param ClientResponseDto[] $items
     */
    public function __construct(
        /** @var ClientResponseDto[] */
        #[OA\Property(
            title: 'Клиенты',
            description: 'Список клиентов.',
            type: 'array',
            items: new OA\Items(ref: ClientResponseDto::class),
        )]
        public array $items,
        #[OA\Property(title: 'Всего записей', description: 'Общее количество клиентов.', example: 42)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущая страница.', example: 1)]
        public int $page,
        #[OA\Property(title: 'Размер страницы', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
