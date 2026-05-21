<?php

declare(strict_types=1);

namespace App\Reporting\Application\UseCase;

use App\Reporting\Application\Dto\GetReportInputDto;
use App\Reporting\Application\Dto\GetReportOutputDto;
use App\Reporting\Application\Port\GetReportOutputPortInterface;
use App\Reporting\Domain\Exception\ReportNotFoundException;
use App\Reporting\Domain\Repository\ReportRepositoryInterface;
use App\Reporting\Domain\ValueObject\ReportId;
use DateTimeInterface;

/** Use Case получения полных данных отчёта по идентификатору, включая строки агрегации. */
final readonly class GetReportUseCase
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository,
        private GetReportOutputPortInterface $presenter,
    ) {}

    public function execute(GetReportInputDto $input): void
    {
        $reportId = new ReportId($input->reportId);
        $report = $this->reportRepository->findById($reportId);

        if (null === $report) {
            throw new ReportNotFoundException($input->reportId);
        }

        $dto = new GetReportOutputDto(
            id: $report->getId()->value(),
            name: $report->getName(),
            createdBy: $report->getCreatedBy(),
            createdAt: $report->getCreatedAt()->format(DateTimeInterface::ATOM),
            periodFrom: $report->getPeriod()->from()->format('Y-m-d'),
            periodTo: $report->getPeriod()->to()->format('Y-m-d'),
            filters: $report->getFilters()->toArray(),
            groupBy: $report->getGroupBy()->toArray(),
            data: $report->getData()->rows(),
        );

        $this->presenter->present($dto);
    }
}
