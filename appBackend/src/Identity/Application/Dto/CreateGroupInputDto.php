<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Входные данные Use Case создания группы. */
final readonly class CreateGroupInputDto
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}
}
