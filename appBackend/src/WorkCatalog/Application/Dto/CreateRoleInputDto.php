<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Входные данные Use Case создания роли. */
final readonly class CreateRoleInputDto
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}
}
