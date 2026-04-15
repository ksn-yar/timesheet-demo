<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Выходные данные Use Case получения списка групп. */
final readonly class ListGroupsOutputDto
{
    /**
     * @param GroupItemDto[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
