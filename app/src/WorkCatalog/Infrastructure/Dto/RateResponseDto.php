<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными ставки. */
#[OA\Schema(
    schema: 'RateResponse',
    description: 'Данные ставки.',
)]
final readonly class RateResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор ставки.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
        public string $id,
        #[OA\Property(title: 'Сумма', description: 'Сумма ставки.', example: '1500.00')]
        public string $amount,
        #[OA\Property(title: 'Валюта', description: 'Код валюты ISO 4217.', example: 'RUB')]
        public string $currency,
        #[OA\Property(title: 'Дата начала действия', description: 'Дата начала действия ставки.', format: 'date', example: '2026-01-01')]
        public string $effectiveFrom,
        #[OA\Property(title: 'ID роли', description: 'Идентификатор привязанной роли.', format: 'uuid', nullable: true)]
        public ?string $roleId = null,
        #[OA\Property(title: 'Название роли', description: 'Название привязанной роли.', nullable: true)]
        public ?string $roleName = null,
        #[OA\Property(title: 'ID вида работ', description: 'Идентификатор привязанного вида работ.', format: 'uuid', nullable: true)]
        public ?string $workId = null,
        #[OA\Property(title: 'Название вида работ', description: 'Название привязанного вида работ.', nullable: true)]
        public ?string $workName = null,
    ) {}
}
