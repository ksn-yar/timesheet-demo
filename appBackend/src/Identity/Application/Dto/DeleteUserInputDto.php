<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Входные данные Use Case удаления пользователя (soft delete). */
final readonly class DeleteUserInputDto
{
    public function __construct(
        public string $userId,
    ) {}
}
