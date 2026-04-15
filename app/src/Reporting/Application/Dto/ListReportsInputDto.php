<?php

declare(strict_types=1);

namespace App\Reporting\Application\Dto;

/** Входные данные Use Case получения списка отчётов с пагинацией и опциональной фильтрацией. */
final readonly class ListReportsInputDto
{
    public function __construct(
        public ?string $createdBy = null,
        public ?string $periodFrom = null,
        public ?string $periodTo = null,
        public ?string $name = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
