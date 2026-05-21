<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Входные данные Use Case получения списка пользователей. */
final readonly class ListUsersInputDto
{
    public function __construct(
        public ?string $groupId = null,
        public ?string $roleId = null,
        public ?bool $isActive = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
