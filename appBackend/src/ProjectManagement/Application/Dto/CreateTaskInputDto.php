<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case создания задачи. */
final readonly class CreateTaskInputDto
{
    public function __construct(
        public string $name,
        public ?string $projectId = null,
        public ?string $crId = null,
        public ?string $description = null,
        public ?float $estimate = null,
    ) {}
}
