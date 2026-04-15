<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

/** DTO входящего HTTP-запроса на обновление политики импорта. */
final readonly class UpdateImportPolicyRequestDto
{
    public function __construct(
        public ?array $mappingRules = null,
        public ?bool $allowEdit = null,
        public ?bool $isActive = null,
    ) {}
}
