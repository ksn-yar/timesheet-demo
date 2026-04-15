<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Входные данные Use Case создания пользователя. */
final readonly class CreateUserInputDto
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $systemRole,
        public ?string $groupId = null,
        public ?string $roleId = null,
    ) {}
}
