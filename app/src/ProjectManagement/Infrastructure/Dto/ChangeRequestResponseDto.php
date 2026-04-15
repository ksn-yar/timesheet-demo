<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными запроса на изменение. */
#[OA\Schema(
    schema: 'ChangeRequestResponse',
    description: 'Данные запроса на изменение.',
)]
final readonly class ChangeRequestResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор запроса на изменение.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
        public string $id,
        #[OA\Property(title: 'Идентификатор проекта', description: 'Идентификатор проекта.', format: 'uuid', example: '660e8400-e29b-41d4-a716-446655440000')]
        public string $projectId,
        #[OA\Property(title: 'Название проекта', description: 'Название проекта.', example: 'Редизайн портала')]
        public string $projectName,
        #[OA\Property(title: 'Название', description: 'Название запроса на изменение.', example: 'CR-001')]
        public string $name,
        #[OA\Property(title: 'Описание', description: 'Описание запроса на изменение.', nullable: true, example: 'Добавить новый модуль')]
        public ?string $description = null,
    ) {}
}
