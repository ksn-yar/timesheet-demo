<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Transformer;

use App\Identity\Application\Dto\CreateUserInputDto;
use App\Identity\Infrastructure\Dto\CreateUserRequestDto;

/** Трансформирует CreateUserRequestDto в CreateUserInputDto для Use Case. */
final readonly class CreateUserInputTransformer
{
    public function transform(CreateUserRequestDto $dto): CreateUserInputDto
    {
        return new CreateUserInputDto(
            name: $dto->name,
            email: $dto->email,
            password: $dto->password,
            systemRole: $dto->systemRole,
            groupId: $dto->groupId,
            roleId: $dto->roleId,
        );
    }
}
