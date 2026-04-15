<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Входные данные Use Case получения списка видов работ. */
final readonly class ListWorksInputDto
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
