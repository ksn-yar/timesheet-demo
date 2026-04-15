<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Transformer;

use App\WorkCatalog\Application\Dto\DeleteRoleInputDto;

/** Трансформирует path-параметр id в DeleteRoleInputDto для Use Case. */
final class DeleteRoleInputTransformer
{
    public function transform(string $id): DeleteRoleInputDto
    {
        return new DeleteRoleInputDto(id: $id);
    }
}
