<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Элемент списка запросов на изменение в выходных данных Use Case. */
final readonly class ChangeRequestItemDto
{
    public function __construct(
        public string $id,
        public string $projectId,
        public string $projectName,
        public string $name,
        public ?string $description = null,
    ) {}
}
