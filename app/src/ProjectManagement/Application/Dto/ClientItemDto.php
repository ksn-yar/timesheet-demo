<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Элемент списка клиентов в выходных данных Use Case. */
final readonly class ClientItemDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
    ) {}
}
