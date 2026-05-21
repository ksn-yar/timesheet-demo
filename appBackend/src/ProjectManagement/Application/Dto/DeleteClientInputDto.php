<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case удаления клиента. */
final readonly class DeleteClientInputDto
{
    public function __construct(
        public string $id,
    ) {}
}
