<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case создания клиента. */
final readonly class CreateClientInputDto
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}
}
