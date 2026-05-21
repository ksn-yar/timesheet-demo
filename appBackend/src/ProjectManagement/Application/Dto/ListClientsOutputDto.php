<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Выходные данные Use Case получения списка клиентов. */
final readonly class ListClientsOutputDto
{
    /**
     * @param ClientItemDto[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
