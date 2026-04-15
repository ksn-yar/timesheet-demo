<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными вида работ. */
#[OA\Schema(
    schema: 'WorkResponse',
    description: 'Данные вида работ.',
)]
final readonly class WorkResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор вида работ.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
        public string $id,
        #[OA\Property(title: 'Название', description: 'Название вида работ.', example: 'Разработка')]
        public string $name,
        #[OA\Property(title: 'Описание', description: 'Описание вида работ.', nullable: true, example: 'Разработка программного обеспечения')]
        public ?string $description = null,
    ) {}
}
