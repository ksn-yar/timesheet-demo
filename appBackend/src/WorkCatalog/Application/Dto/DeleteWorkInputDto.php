<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Dto;

/** Входные данные Use Case удаления вида работ. */
final readonly class DeleteWorkInputDto
{
    public function __construct(
        public string $id,
    ) {}
}
