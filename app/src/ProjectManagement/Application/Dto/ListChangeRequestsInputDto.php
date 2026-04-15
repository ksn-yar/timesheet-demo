<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case получения списка запросов на изменение. */
final readonly class ListChangeRequestsInputDto
{
    public function __construct(
        public ?string $projectId = null,
        public ?string $name = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
