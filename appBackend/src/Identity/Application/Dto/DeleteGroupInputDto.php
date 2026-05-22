<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Входные данные Use Case удаления группы (soft delete). */
final readonly class DeleteGroupInputDto
{
    public function __construct(
        public string $groupId,
    ) {}
}
