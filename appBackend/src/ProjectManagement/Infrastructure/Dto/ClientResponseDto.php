<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными клиента. */
#[OA\Schema(
    schema: 'ClientResponse',
    description: 'Данные клиента.',
)]
final readonly class ClientResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор клиента.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
        public string $id,
        #[OA\Property(title: 'Название', description: 'Название клиента.', example: 'Acme Corp')]
        public string $name,
        #[OA\Property(title: 'Описание', description: 'Описание клиента.', nullable: true, example: 'Крупный заказчик')]
        public ?string $description = null,
    ) {}
}
