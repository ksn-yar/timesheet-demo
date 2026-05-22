<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Элемент списка задач в выходных данных Use Case. */
final readonly class TaskItemDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $projectId = null,
        public ?string $projectName = null,
        public ?string $crId = null,
        public ?string $crName = null,
        public ?string $description = null,
        public ?float $estimate = null,
    ) {}
}
