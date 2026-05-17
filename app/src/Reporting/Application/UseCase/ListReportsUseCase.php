<?php

declare(strict_types=1);

namespace App\Reporting\Application\UseCase;

use App\Reporting\Application\Dto\ListReportsInputDto;
use App\Reporting\Application\Dto\ListReportsOutputDto;
use App\Reporting\Application\Dto\ReportItemDto;
use App\Reporting\Application\Port\ListReportsOutputPortInterface;
use App\Reporting\Domain\Entity\Report;
use App\Reporting\Domain\Repository\ReportRepositoryInterface;
use DateTimeInterface;

/** Use Case получения списка отчётов с пагинацией и опциональной фильтрацией по метаданным. */
final readonly class ListReportsUseCase
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository,
        private ListReportsOutputPortInterface $presenter,
    ) {}

    public function execute(ListReportsInputDto $input): void
    {
        $criteria = $this->buildCriteria($input);

        $reports = $this->reportRepository->findAll($criteria);
        $total = $this->reportRepository->count($criteria);

        $items = array_map(
            static fn (Report $report): ReportItemDto => new ReportItemDto(
                id: $report->getId()->value(),
                name: $report->getName(),
                createdBy: $report->getCreatedBy(),
                createdAt: $report->getCreatedAt()->format(DateTimeInterface::ATOM),
                periodFrom: $report->getPeriod()->from()->format('Y-m-d'),
                periodTo: $report->getPeriod()->to()->format('Y-m-d'),
                groupBy: $report->getGroupBy()->toArray(),
            ),
            $reports,
        );

        $this->presenter->present(new ListReportsOutputDto(
            items: $items,
            total: $total,
            page: $input->page,
            perPage: $input->perPage,
        ));
    }

    /** @return array<string, mixed> */
    private function buildCriteria(ListReportsInputDto $input): array
    {
        $criteria = [
            'page' => $input->page,
            'perPage' => $input->perPage,
        ];

        if (null !== $input->createdBy) {
            $criteria['createdBy'] = $input->createdBy;
        }

        if (null !== $input->periodFrom) {
            $criteria['periodFrom'] = $input->periodFrom;
        }

        if (null !== $input->periodTo) {
            $criteria['periodTo'] = $input->periodTo;
        }

        if (null !== $input->name) {
            $criteria['name'] = $input->name;
        }

        return $criteria;
    }
}
