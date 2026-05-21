<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Выходные данные Use Case получения списка ставок. */
final readonly class ListRatesOutputDto
{
    /**
     * @param RateItemDto[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
