<?php

declare(strict_types=1);

namespace App\Reporting\Application\UseCase;

use App\Reporting\Application\Dto\ExportReportsInputDto;
use App\Reporting\Application\Port\ReportFileGeneratorInterface;
use App\Reporting\Domain\Enum\ExportFormat;
use App\Reporting\Domain\Event\ReportsExported;
use App\Reporting\Domain\Exception\ReportNotFoundException;
use App\Reporting\Domain\Repository\ReportExportRepositoryInterface;
use App\Reporting\Domain\Repository\ReportRepositoryInterface;
use App\Reporting\Domain\ValueObject\ReportExport;
use App\Reporting\Domain\ValueObject\ReportExportId;
use App\Reporting\Domain\ValueObject\ReportId;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case экспорта набора отчётов в файл заданного формата. */
final readonly class ExportReportsUseCase
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository,
        private ReportExportRepositoryInterface $exportRepository,
        private ReportFileGeneratorInterface $fileGenerator,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(ExportReportsInputDto $input): void
    {
        if (empty($input->reportIds)) {
            throw new InvalidArgumentException('Список идентификаторов отчётов не может быть пустым.');
        }

        $reports = [];

        foreach ($input->reportIds as $reportId) {
            $report = $this->reportRepository->findById(new ReportId($reportId));

            if (null === $report) {
                throw new ReportNotFoundException($reportId);
            }

            $reports[] = $report;
        }

        $format = ExportFormat::from($input->format);
        $fileRef = $this->fileGenerator->generate($reports, $format);

        $generatedAt = new DateTimeImmutable();

        $export = new ReportExport(
            new ReportExportId($input->exportId),
            $input->reportIds,
            $format,
            $generatedAt,
            $fileRef,
        );

        $this->exportRepository->save($export);

        // ReportExport — Value Object, события публикуются напрямую без трейта
        $this->eventDispatcher->dispatch(new ReportsExported(
            reportIds: $input->reportIds,
            format: $format,
            fileRef: $fileRef,
            generatedAt: $generatedAt,
        ));
    }
}
