<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Элемент списка ставок в выходных данных Use Case. */
final readonly class RateItemDto
{
    public function __construct(
        public string $id,
        public string $amount,
        public string $currency,
        public string $effectiveFrom,
        public ?string $roleId = null,
        public ?string $roleName = null,
        public ?string $workId = null,
        public ?string $workName = null,
    ) {}
}
