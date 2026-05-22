<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Входные данные Use Case изменения принадлежности пользователя к группе. */
final readonly class ChangeUserGroupInputDto
{
    public function __construct(
        public string $userId,
        public ?string $groupId = null,
    ) {}
}
