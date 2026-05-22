<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными политики импорта. */
#[OA\Schema(
    schema: 'ImportPolicyResponse',
    description: 'Данные политики импорта.',
)]
final readonly class ImportPolicyResponseDto
{
    /** @param array<string, mixed> $mappingRules */
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор политики.', format: 'uuid')]
        public string $id,
        #[OA\Property(title: 'Название', description: 'Название политики импорта.')]
        public string $name,
        #[OA\Property(title: 'Система-источник', description: 'Название системы-источника данных.')]
        public string $sourceSystem,
        #[OA\Property(title: 'Правила маппинга', description: 'Правила маппинга полей.', type: 'array', items: new OA\Items(type: 'object'))]
        public array $mappingRules,
        #[OA\Property(title: 'Разрешение редактирования', description: 'Разрешено ли редактирование импортированных тикетов.')]
        public bool $allowEdit,
        #[OA\Property(title: 'Активна', description: 'Активна ли политика.')]
        public bool $isActive,
    ) {}
}
