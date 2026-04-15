<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Transformer;

use App\Identity\Application\Dto\CreateGroupInputDto;
use App\Identity\Infrastructure\Dto\CreateGroupRequestDto;

/** Трансформирует CreateGroupRequestDto в CreateGroupInputDto для Use Case. */
final class CreateGroupInputTransformer
{
    public function transform(CreateGroupRequestDto $dto): CreateGroupInputDto
    {
        return new CreateGroupInputDto(
            name: $dto->name,
            description: $dto->description,
        );
    }
}
