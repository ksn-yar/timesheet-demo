<?php

declare(strict_types=1);

namespace App\Reporting\Application\Dto;

/** Выходные данные Use Case получения списка отчётов с пагинацией. */
final readonly class ListReportsOutputDto
{
    /** @param ReportItemDto[] $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
