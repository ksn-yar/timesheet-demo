<?php

declare(strict_types=1);

namespace App\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\RunImportInputDto;
use App\Timesheet\Application\Dto\RunImportOutputDto;
use App\Timesheet\Application\Port\EmployeeResolverInterface;
use App\Timesheet\Application\Port\ExternalDataFetcherInterface;
use App\Timesheet\Application\Port\RateProviderInterface;
use App\Timesheet\Application\Port\RunImportOutputPortInterface;
use App\Timesheet\Application\Port\TaskResolverInterface;
use App\Timesheet\Application\Port\WorkResolverInterface;
use App\Timesheet\Domain\Entity\Ticket;
use App\Timesheet\Domain\Enum\ImportLogEntryStatus;
use App\Timesheet\Domain\Event\TicketsImported;
use App\Timesheet\Domain\Exception\ImportPolicyNotActiveException;
use App\Timesheet\Domain\Exception\ImportPolicyNotFoundException;
use App\Timesheet\Domain\Repository\ImportPolicyRepositoryInterface;
use App\Timesheet\Domain\Repository\TicketRepositoryInterface;
use App\Timesheet\Domain\ValueObject\ImportLogEntry;
use App\Timesheet\Domain\ValueObject\ImportPolicyId;
use App\Timesheet\Domain\ValueObject\TicketId;
use DateTimeImmutable;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case запуска импорта тикетов из внешней системы по политике импорта. */
final readonly class RunImportUseCase
{
    public function __construct(
        private ImportPolicyRepositoryInterface $importPolicyRepository,
        private TicketRepositoryInterface $ticketRepository,
        private EmployeeResolverInterface $employeeResolver,
        private TaskResolverInterface $taskResolver,
        private WorkResolverInterface $workResolver,
        private RateProviderInterface $rateProvider,
        private ExternalDataFetcherInterface $externalDataFetcher,
        private RunImportOutputPortInterface $presenter,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(RunImportInputDto $input): void
    {
        $policy = $this->importPolicyRepository->findById(new ImportPolicyId($input->importPolicyId));

        if (null === $policy) {
            throw new ImportPolicyNotFoundException($input->importPolicyId);
        }

        if (!$policy->isActive()) {
            throw new ImportPolicyNotActiveException($input->importPolicyId);
        }

        $records = $this->externalDataFetcher->fetch(
            $policy->getSourceSystem(),
            $policy->getMappingRules(),
            $input->dateFrom,
            $input->dateTo,
        );

        $imported = 0;
        $duplicates = 0;
        $errors = 0;

        /** @var ImportLogEntry[] $logEntries */
        $logEntries = [];

        $mappingRules = $policy->getMappingRules();

        /** @var array<string, mixed> $employeeMappingRules */
        $employeeMappingRules = $mappingRules['employeeMapping'] ?? [];

        /** @var array<string, mixed> $taskMappingRules */
        $taskMappingRules = $mappingRules['taskMapping'] ?? [];

        /** @var array<string, mixed> $workMappingRules */
        $workMappingRules = $mappingRules['workMapping'] ?? [];

        foreach ($records as $record) {
            $externalId = (string) ($record['externalId'] ?? '');

            if ($this->ticketRepository->existsByImportSourceAndExternalId($policy->getSourceSystem(), $externalId)) {
                ++$duplicates;
                $logEntries[] = new ImportLogEntry($externalId, ImportLogEntryStatus::Duplicate);

                continue;
            }

            $employeeMatchBy = (string) ($employeeMappingRules['matchBy'] ?? 'email');
            $employeeField = (string) ($employeeMappingRules['field'] ?? 'employee');
            $employeeValue = (string) ($record[$employeeField] ?? '');
            $employeeId = $this->employeeResolver->resolve($employeeValue, $employeeMatchBy);

            $taskMatchBy = (string) ($taskMappingRules['matchBy'] ?? 'name');
            $taskField = (string) ($taskMappingRules['field'] ?? 'task');
            $taskValue = (string) ($record[$taskField] ?? '');
            $taskId = $this->taskResolver->resolve($taskValue, $taskMatchBy);

            $workMatchBy = (string) ($workMappingRules['matchBy'] ?? 'name');
            $workField = (string) ($workMappingRules['field'] ?? 'work');
            $workValue = (string) ($record[$workField] ?? '');
            $workId = $this->workResolver->resolve($workValue, $workMatchBy);

            $unmapped = [];

            if (null === $employeeId) {
                $unmapped[] = "сотрудник «{$employeeValue}»";
            }

            if (null === $taskId) {
                $unmapped[] = "задача «{$taskValue}»";
            }

            if (null === $workId) {
                $unmapped[] = "вид работ «{$workValue}»";
            }

            if ([] !== $unmapped) {
                $reason = 'Не удалось сопоставить: ' . implode(', ', $unmapped);
                ++$errors;
                $logEntries[] = new ImportLogEntry($externalId, ImportLogEntryStatus::Error, $reason);

                continue;
            }

            $rateSnapshot = $this->rateProvider->getCurrentRate($employeeId, $workId);

            $dateField = (string) ($mappingRules['dateField'] ?? 'date');
            $hoursField = (string) ($mappingRules['hoursField'] ?? 'hours');
            $commentField = (string) ($mappingRules['commentField'] ?? 'comment');
            $dateValue = (string) ($record[$dateField] ?? 'now');
            $hoursValue = (string) ($record[$hoursField] ?? '0');
            $rawComment = $record[$commentField] ?? null;
            $comment = null !== $rawComment ? (string) $rawComment : null;

            $ticket = Ticket::createImported(
                TicketId::generate(),
                $employeeId,
                $taskId,
                $workId,
                new DateTimeImmutable($dateValue),
                $hoursValue,
                $comment,
                $rateSnapshot,
                $policy->getSourceSystem(),
                $externalId,
                $policy->isAllowEdit(),
            );

            $this->ticketRepository->save($ticket);
            ++$imported;
            $logEntries[] = new ImportLogEntry($externalId, ImportLogEntryStatus::Imported);
        }

        $this->eventDispatcher->dispatch(new TicketsImported(
            $policy->getId(),
            $imported,
            $duplicates,
            $errors,
        ));

        $this->presenter->present(new RunImportOutputDto(
            imported: $imported,
            duplicates: $duplicates,
            errors: $errors,
            logEntries: array_map(
                static fn (ImportLogEntry $entry): array => [
                    'externalId' => $entry->externalId(),
                    'status' => $entry->status()->value,
                    'errorReason' => $entry->errorReason(),
                ],
                $logEntries,
            ),
        ));
    }
}
