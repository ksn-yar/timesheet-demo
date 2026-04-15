<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком проектов и метаданными пагинации. */
#[OA\Schema(
    schema: 'ProjectListResponse',
    description: 'Список проектов с пагинацией.',
)]
final readonly class ProjectListResponseDto
{
    /**
     * @param ProjectResponseDto[] $items
     */
    public function __construct(
        /** @var ProjectResponseDto[] */
        #[OA\Property(
            title: 'Проекты',
            description: 'Список проектов.',
            type: 'array',
            items: new OA\Items(ref: ProjectResponseDto::class),
        )]
        public array $items,
        #[OA\Property(title: 'Всего записей', description: 'Общее количество проектов.', example: 42)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущая страница.', example: 1)]
        public int $page,
        #[OA\Property(title: 'Размер страницы', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
