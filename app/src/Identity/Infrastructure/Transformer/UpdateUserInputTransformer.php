<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Transformer;

use App\Identity\Application\Dto\UpdateUserInputDto;
use App\Identity\Infrastructure\Dto\UpdateUserRequestDto;

/** Трансформирует UpdateUserRequestDto в UpdateUserInputDto для Use Case. */
final readonly class UpdateUserInputTransformer
{
    public function transform(string $id, UpdateUserRequestDto $dto): UpdateUserInputDto
    {
        return new UpdateUserInputDto(
            userId: $id,
            name: $dto->name,
            systemRole: $dto->systemRole,
            roleId: $dto->roleId,
        );
    }
}
