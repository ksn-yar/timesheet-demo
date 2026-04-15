<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Выходные данные Use Case получения списка пользователей. */
final readonly class ListUsersOutputDto
{
    /**
     * @param UserItemDto[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
