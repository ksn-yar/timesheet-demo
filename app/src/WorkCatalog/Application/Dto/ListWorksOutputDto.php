<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Выходные данные Use Case получения списка видов работ. */
final readonly class ListWorksOutputDto
{
    /**
     * @param WorkItemDto[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
