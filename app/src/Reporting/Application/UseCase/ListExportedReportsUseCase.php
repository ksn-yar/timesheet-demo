<?php

declare(strict_types=1);

namespace App\Reporting\Application\UseCase;

use App\Reporting\Application\Dto\ExportItemDto;
use App\Reporting\Application\Dto\ListExportedReportsInputDto;
use App\Reporting\Application\Dto\ListExportedReportsOutputDto;
use App\Reporting\Application\Port\ListExportedReportsOutputPortInterface;
use App\Reporting\Domain\Repository\ReportExportRepositoryInterface;
use App\Reporting\Domain\ValueObject\ReportExport;
use DateTimeInterface;

/** Use Case получения списка выгрузок отчётов с пагинацией и опциональной фильтрацией. */
final class ListExportedReportsUseCase
{
    public function __construct(
        private readonly ReportExportRepositoryInterface $exportRepository,
        private readonly ListExportedReportsOutputPortInterface $presenter,
    ) {}

    public function execute(ListExportedReportsInputDto $input): void
    {
        $criteria = $this->buildCriteria($input);

        $exports = $this->exportRepository->findAll($criteria);
        $total = $this->exportRepository->count($criteria);

        $items = array_map(
            static fn (ReportExport $export): ExportItemDto => new ExportItemDto(
                id: $export->id()->value(),
                reportIds: $export->reportIds(),
                format: $export->format()->value,
                generatedAt: $export->generatedAt()->format(DateTimeInterface::ATOM),
                fileRef: $export->fileRef(),
            ),
            $exports,
        );

        $this->presenter->present(new ListExportedReportsOutputDto(
            items: $items,
            total: $total,
            page: $input->page,
            perPage: $input->perPage,
        ));
    }

    /** @return array<string, mixed> */
    private function buildCriteria(ListExportedReportsInputDto $input): array
    {
        $criteria = [
            'page' => $input->page,
            'perPage' => $input->perPage,
        ];

        if (null !== $input->format) {
            $criteria['format'] = $input->format;
        }

        if (null !== $input->generatedAtFrom) {
            $criteria['generatedAtFrom'] = $input->generatedAtFrom;
        }

        if (null !== $input->generatedAtTo) {
            $criteria['generatedAtTo'] = $input->generatedAtTo;
        }

        return $criteria;
    }
}
