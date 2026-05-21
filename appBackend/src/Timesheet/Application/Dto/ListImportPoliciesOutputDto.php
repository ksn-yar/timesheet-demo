<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Выходные данные Use Case получения списка политик импорта. */
final readonly class ListImportPoliciesOutputDto
{
    /**
     * @param ImportPolicyItemDto[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
