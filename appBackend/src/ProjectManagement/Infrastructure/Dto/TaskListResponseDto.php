<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком задач и метаданными пагинации. */
#[OA\Schema(
    schema: 'TaskListResponse',
    description: 'Список задач с пагинацией.',
)]
final readonly class TaskListResponseDto
{
    /**
     * @param TaskResponseDto[] $items
     */
    public function __construct(
        /** @var TaskResponseDto[] */
        #[OA\Property(
            title: 'Задачи',
            description: 'Список задач.',
            type: 'array',
            items: new OA\Items(ref: TaskResponseDto::class),
        )]
        public array $items,
        #[OA\Property(title: 'Всего записей', description: 'Общее количество задач.', example: 42)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущая страница.', example: 1)]
        public int $page,
        #[OA\Property(title: 'Размер страницы', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
