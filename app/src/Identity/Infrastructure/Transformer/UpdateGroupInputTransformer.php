<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Transformer;

use App\Identity\Application\Dto\UpdateGroupInputDto;
use App\Identity\Infrastructure\Dto\UpdateGroupRequestDto;

/** Трансформирует UpdateGroupRequestDto в UpdateGroupInputDto для Use Case. */
final class UpdateGroupInputTransformer
{
    public function transform(string $id, UpdateGroupRequestDto $dto): UpdateGroupInputDto
    {
        return new UpdateGroupInputDto(
            groupId: $id,
            name: $dto->name,
            description: $dto->description,
        );
    }
}
