<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Входные данные Use Case обновления политики импорта. */
final readonly class UpdateImportPolicyInputDto
{
    /** @param null|array<string, mixed> $mappingRules */
    public function __construct(
        public string $importPolicyId,
        public ?array $mappingRules = null,
        public ?bool $allowEdit = null,
        public ?bool $isActive = null,
    ) {}
}
