<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными группы. */
#[OA\Schema(
    schema: 'GroupResponse',
    description: 'Данные группы.',
)]
final readonly class GroupResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор группы.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
        public string $id,
        #[OA\Property(title: 'Название', description: 'Название группы.', example: 'Отдел разработки')]
        public string $name,
        #[OA\Property(title: 'Описание', description: 'Описание группы.', nullable: true, example: 'Команда backend-разработчиков')]
        public ?string $description = null,
    ) {}
}
