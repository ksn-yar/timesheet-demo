<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Элемент списка ролей в выходных данных Use Case. */
final readonly class RoleItemDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
    ) {}
}
