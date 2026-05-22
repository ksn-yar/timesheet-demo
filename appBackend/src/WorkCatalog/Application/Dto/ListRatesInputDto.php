<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Входные данные Use Case получения списка ставок. */
final readonly class ListRatesInputDto
{
    public function __construct(
        public ?string $roleId = null,
        public ?string $workId = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
