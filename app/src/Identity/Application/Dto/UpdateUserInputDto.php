<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Входные данные Use Case обновления пользователя. */
final readonly class UpdateUserInputDto
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $systemRole,
        public ?string $roleId = null,
    ) {}
}
