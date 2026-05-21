<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Входные данные Use Case получения списка групп. */
final readonly class ListGroupsInputDto
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
