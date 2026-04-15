<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Элемент списка пользователей в выходных данных Use Case. */
final readonly class UserItemDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $systemRole,
        public ?string $groupId,
        public ?string $groupName,
        public ?string $roleId,
        public ?string $roleName,
        public bool $isActive,
    ) {}
}
