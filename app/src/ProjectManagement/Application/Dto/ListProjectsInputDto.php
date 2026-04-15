<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case получения списка проектов. */
final readonly class ListProjectsInputDto
{
    public function __construct(
        public ?string $clientId = null,
        public ?string $status = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
