<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком тикетов и метаданными пагинации. */
#[OA\Schema(
    schema: 'TicketListResponse',
    description: 'Список тикетов с пагинацией.',
)]
final readonly class TicketListResponseDto
{
    /**
     * @param TicketResponseDto[] $items
     */
    public function __construct(
        /** @var TicketResponseDto[] */
        #[OA\Property(
            title: 'Тикеты',
            description: 'Список тикетов.',
            type: 'array',
            items: new OA\Items(ref: TicketResponseDto::class),
        )]
        public array $items,
        #[OA\Property(title: 'Всего записей', description: 'Общее количество тикетов.', example: 42)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущая страница.', example: 1)]
        public int $page,
        #[OA\Property(title: 'Размер страницы', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
