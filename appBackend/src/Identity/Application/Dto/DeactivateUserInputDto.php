<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Входные данные Use Case деактивации пользователя. */
final readonly class DeactivateUserInputDto
{
    public function __construct(
        public string $userId,
    ) {}
}
