<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Transformer;

use App\Identity\Application\Dto\ListUsersInputDto;
use App\Identity\Infrastructure\Dto\ListUsersRequestDto;

/** Трансформирует ListUsersRequestDto в ListUsersInputDto для Use Case. */
final class ListUsersInputTransformer
{
    public function transform(ListUsersRequestDto $dto): ListUsersInputDto
    {
        return new ListUsersInputDto(
            groupId: $dto->groupId,
            roleId: $dto->roleId,
            isActive: $dto->isActive,
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
