<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными роли. */
#[OA\Schema(
    schema: 'RoleResponse',
    description: 'Данные роли.',
)]
final readonly class RoleResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор роли.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
        public string $id,
        #[OA\Property(title: 'Название', description: 'Название роли.', example: 'Разработчик')]
        public string $name,
        #[OA\Property(title: 'Описание', description: 'Описание роли.', nullable: true, example: 'Программист-разработчик')]
        public ?string $description = null,
    ) {}
}
