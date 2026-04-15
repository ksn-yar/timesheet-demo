<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case получения списка задач. */
final readonly class ListTasksInputDto
{
    public function __construct(
        public ?string $projectId = null,
        public ?string $crId = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
