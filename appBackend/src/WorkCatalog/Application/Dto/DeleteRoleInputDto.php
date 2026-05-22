<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Входные данные Use Case удаления роли. */
final readonly class DeleteRoleInputDto
{
    public function __construct(
        public string $id,
    ) {}
}
