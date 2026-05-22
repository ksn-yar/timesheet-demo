<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными проекта. */
#[OA\Schema(
    schema: 'ProjectResponse',
    description: 'Данные проекта.',
)]
final readonly class ProjectResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор проекта.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
        public string $id,
        #[OA\Property(title: 'Идентификатор клиента', description: 'Идентификатор клиента-владельца.', format: 'uuid', example: '660e8400-e29b-41d4-a716-446655440000')]
        public string $clientId,
        #[OA\Property(title: 'Название клиента', description: 'Название клиента-владельца.', example: 'Acme Corp')]
        public string $clientName,
        #[OA\Property(title: 'Название', description: 'Название проекта.', example: 'Редизайн портала')]
        public string $name,
        #[OA\Property(title: 'Статус', description: 'Статус проекта.', example: 'active')]
        public string $status,
        #[OA\Property(title: 'Метка статуса', description: 'Человекочитаемая метка статуса.', example: 'Активный')]
        public string $statusLabel,
        #[OA\Property(title: 'Описание', description: 'Описание проекта.', nullable: true, example: 'Редизайн клиентского портала')]
        public ?string $description = null,
    ) {}
}
