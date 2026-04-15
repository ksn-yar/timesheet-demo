<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Входные данные Use Case удаления ставки. */
final readonly class DeleteRateInputDto
{
    public function __construct(
        public string $id,
    ) {}
}
