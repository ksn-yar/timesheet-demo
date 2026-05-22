<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Presenter;

use App\Reporting\Application\Dto\GetReportOutputDto;
use App\Reporting\Application\Port\GetReportOutputPortInterface;
use App\Reporting\Infrastructure\Dto\ReportResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения отчёта. Формирует полный Response DTO. */
final class HttpGetReportPresenter implements GetReportOutputPortInterface
{
    private ?GetReportOutputDto $dto = null;

    public function present(GetReportOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): ReportResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        return new ReportResponseDto(
            id: $this->dto->id,
            name: $this->dto->name,
            createdBy: $this->dto->createdBy,
            createdAt: $this->dto->createdAt,
            periodFrom: $this->dto->periodFrom,
            periodTo: $this->dto->periodTo,
            filters: $this->dto->filters,
            groupBy: $this->dto->groupBy,
            data: $this->dto->data,
        );
    }
}
