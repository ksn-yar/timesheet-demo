<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Входные данные Use Case обновления ставки. */
final readonly class UpdateRateInputDto
{
    public function __construct(
        public string $id,
        public ?string $amount = null,
        public ?string $currency = null,
        public ?string $effectiveFrom = null,
        public ?string $roleId = null,
        public ?string $workId = null,
    ) {}
}
