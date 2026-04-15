<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Transformer;

use App\Identity\Application\Dto\ChangeUserGroupInputDto;
use App\Identity\Infrastructure\Dto\ChangeUserGroupRequestDto;

/** Трансформирует ChangeUserGroupRequestDto в ChangeUserGroupInputDto для Use Case. */
final class ChangeUserGroupInputTransformer
{
    public function transform(string $id, ChangeUserGroupRequestDto $dto): ChangeUserGroupInputDto
    {
        return new ChangeUserGroupInputDto(
            userId: $id,
            groupId: $dto->groupId,
        );
    }
}
