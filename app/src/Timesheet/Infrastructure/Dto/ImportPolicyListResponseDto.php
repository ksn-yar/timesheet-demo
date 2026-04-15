<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API со списком политик импорта и метаданными пагинации. */
#[OA\Schema(
    schema: 'ImportPolicyListResponse',
    description: 'Список политик импорта с пагинацией.',
)]
final readonly class ImportPolicyListResponseDto
{
    /**
     * @param ImportPolicyResponseDto[] $items
     */
    public function __construct(
        /** @var ImportPolicyResponseDto[] */
        #[OA\Property(
            title: 'Политики импорта',
            description: 'Список политик импорта.',
            type: 'array',
            items: new OA\Items(ref: ImportPolicyResponseDto::class),
        )]
        public array $items,
        #[OA\Property(title: 'Всего записей', description: 'Общее количество политик.', example: 5)]
        public int $total,
        #[OA\Property(title: 'Страница', description: 'Текущая страница.', example: 1)]
        public int $page,
        #[OA\Property(title: 'Размер страницы', description: 'Количество элементов на странице.', example: 20)]
        public int $perPage,
    ) {}
}
