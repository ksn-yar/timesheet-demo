<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case удаления проекта. */
final readonly class DeleteProjectInputDto
{
    public function __construct(
        public string $id,
    ) {}
}
