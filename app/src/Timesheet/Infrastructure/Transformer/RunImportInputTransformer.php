<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\RunImportInputDto;
use App\Timesheet\Infrastructure\Dto\RunImportRequestDto;
use DateTimeImmutable;

/** Трансформирует HTTP DTO запуска импорта в Application DTO. */
final readonly class RunImportInputTransformer
{
    public function transform(string $importPolicyId, RunImportRequestDto $dto): RunImportInputDto
    {
        return new RunImportInputDto(
            importPolicyId: $importPolicyId,
            dateFrom: new DateTimeImmutable($dto->dateFrom),
            dateTo: new DateTimeImmutable($dto->dateTo),
        );
    }
}
