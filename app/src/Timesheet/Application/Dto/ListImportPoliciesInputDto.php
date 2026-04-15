<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Входные данные Use Case получения списка политик импорта. */
final readonly class ListImportPoliciesInputDto
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
