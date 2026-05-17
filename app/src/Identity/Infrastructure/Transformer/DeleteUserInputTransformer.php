<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Transformer;

use App\Identity\Application\Dto\DeleteUserInputDto;

/** Трансформирует path-параметр id в DeleteUserInputDto для Use Case. */
final readonly class DeleteUserInputTransformer
{
    public function transform(string $id): DeleteUserInputDto
    {
        return new DeleteUserInputDto(userId: $id);
    }
}
