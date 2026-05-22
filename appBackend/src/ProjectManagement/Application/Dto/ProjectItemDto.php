<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Элемент списка проектов в выходных данных Use Case. */
final readonly class ProjectItemDto
{
    public function __construct(
        public string $id,
        public string $clientId,
        public string $clientName,
        public string $name,
        public string $status,
        public string $statusLabel,
        public ?string $description = null,
    ) {}
}
