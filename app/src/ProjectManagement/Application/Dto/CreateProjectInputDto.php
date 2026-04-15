<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case создания проекта. */
final readonly class CreateProjectInputDto
{
    public function __construct(
        public string $clientId,
        public string $name,
        public string $status,
        public ?string $description = null,
    ) {}
}
