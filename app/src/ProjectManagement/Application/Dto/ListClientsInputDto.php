<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case получения списка клиентов. */
final readonly class ListClientsInputDto
{
    public function __construct(
        public ?string $name = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
