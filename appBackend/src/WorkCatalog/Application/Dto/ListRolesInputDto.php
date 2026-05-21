<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Входные данные Use Case получения списка ролей. */
final readonly class ListRolesInputDto
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
