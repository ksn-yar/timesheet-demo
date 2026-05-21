<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Входные данные Use Case создания вида работ. */
final readonly class CreateWorkInputDto
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}
}
