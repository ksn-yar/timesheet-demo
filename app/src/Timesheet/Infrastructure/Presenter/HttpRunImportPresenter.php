<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Presenter;

use App\Timesheet\Application\Dto\RunImportOutputDto;
use App\Timesheet\Application\Port\RunImportOutputPortInterface;
use App\Timesheet\Infrastructure\Dto\ImportSummaryResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case запуска импорта. Формирует Response DTO. */
final class HttpRunImportPresenter implements RunImportOutputPortInterface
{
    private ?RunImportOutputDto $dto = null;

    public function present(RunImportOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): ImportSummaryResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        return new ImportSummaryResponseDto(
            imported: $this->dto->imported,
            duplicates: $this->dto->duplicates,
            errors: $this->dto->errors,
            logEntries: $this->dto->logEntries,
        );
    }
}
