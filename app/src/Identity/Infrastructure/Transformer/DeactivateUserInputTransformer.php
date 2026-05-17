<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Transformer;

use App\Identity\Application\Dto\DeactivateUserInputDto;

/** Трансформирует path-параметр id в DeactivateUserInputDto для Use Case. */
final readonly class DeactivateUserInputTransformer
{
    public function transform(string $id): DeactivateUserInputDto
    {
        return new DeactivateUserInputDto(userId: $id);
    }
}
