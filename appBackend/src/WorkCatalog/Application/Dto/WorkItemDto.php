<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Элемент списка видов работ в выходных данных Use Case. */
final readonly class WorkItemDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
    ) {}
}
