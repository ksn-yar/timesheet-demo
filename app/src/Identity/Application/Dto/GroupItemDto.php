<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Элемент списка групп в выходных данных Use Case. */
final readonly class GroupItemDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
    ) {}
}
