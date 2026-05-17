<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Transformer;

use App\Identity\Application\Dto\DeleteGroupInputDto;

/** Трансформирует path-параметр id в DeleteGroupInputDto для Use Case. */
final readonly class DeleteGroupInputTransformer
{
    public function transform(string $id): DeleteGroupInputDto
    {
        return new DeleteGroupInputDto(groupId: $id);
    }
}
