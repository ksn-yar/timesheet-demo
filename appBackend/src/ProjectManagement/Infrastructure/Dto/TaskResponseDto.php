<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными задачи. */
#[OA\Schema(
    schema: 'TaskResponse',
    description: 'Данные задачи.',
)]
final readonly class TaskResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор задачи.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
        public string $id,
        #[OA\Property(title: 'Идентификатор проекта', description: 'Идентификатор проекта.', format: 'uuid', nullable: true, example: '660e8400-e29b-41d4-a716-446655440000')]
        public ?string $projectId,
        #[OA\Property(title: 'Название проекта', description: 'Название проекта.', nullable: true, example: 'Редизайн портала')]
        public ?string $projectName,
        #[OA\Property(title: 'Идентификатор CR', description: 'Идентификатор запроса на изменение.', format: 'uuid', nullable: true, example: '770e8400-e29b-41d4-a716-446655440000')]
        public ?string $crId,
        #[OA\Property(title: 'Название CR', description: 'Название запроса на изменение.', nullable: true, example: 'CR-001')]
        public ?string $crName,
        #[OA\Property(title: 'Название', description: 'Название задачи.', example: 'Реализовать модуль авторизации')]
        public string $name,
        #[OA\Property(title: 'Описание', description: 'Описание задачи.', nullable: true, example: 'Реализовать OAuth 2.0')]
        public ?string $description,
        #[OA\Property(title: 'Оценка', description: 'Оценка трудозатрат в часах.', nullable: true, example: 16.0)]
        public ?float $estimate,
    ) {}
}
