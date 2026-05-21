<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Входные данные Use Case создания ставки. */
final readonly class CreateRateInputDto
{
    public function __construct(
        public string $amount,
        public string $currency,
        public string $effectiveFrom,
        public ?string $roleId = null,
        public ?string $workId = null,
    ) {}
}
