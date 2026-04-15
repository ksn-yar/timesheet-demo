<?php

declare(strict_types=1);

namespace App\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\CreateManualTicketInputDto;
use App\Timesheet\Application\Port\CurrentUserProviderInterface;
use App\Timesheet\Application\Port\RateProviderInterface;
use App\Timesheet\Application\Port\TaskExistenceCheckerInterface;
use App\Timesheet\Application\Port\WorkExistenceCheckerInterface;
use App\Timesheet\Domain\Entity\Ticket;
use App\Timesheet\Domain\Exception\TicketOwnershipViolationException;
use App\Timesheet\Domain\Repository\TicketRepositoryInterface;
use App\Timesheet\Domain\ValueObject\TicketId;
use DomainException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case создания тикета вручную с проверкой прав и валидацией связанных сущностей. */
final class CreateManualTicketUseCase
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepository,
        private readonly TaskExistenceCheckerInterface $taskExistenceChecker,
        private readonly WorkExistenceCheckerInterface $workExistenceChecker,
        private readonly RateProviderInterface $rateProvider,
        private readonly CurrentUserProviderInterface $currentUserProvider,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateManualTicketInputDto $input): void
    {
        $currentUserId = $this->currentUserProvider->getCurrentUserId();

        if (!$this->currentUserProvider->isAdmin() && $input->employeeId !== $currentUserId) {
            throw new TicketOwnershipViolationException();
        }

        if (!$this->taskExistenceChecker->taskExists($input->taskId)) {
            throw new DomainException("Задача с идентификатором «{$input->taskId}» не найдена.");
        }

        if (!$this->workExistenceChecker->workExists($input->workId)) {
            throw new DomainException("Вид работ с идентификатором «{$input->workId}» не найден.");
        }

        $rateSnapshot = $this->rateProvider->getCurrentRate($input->employeeId, $input->workId);

        $ticket = Ticket::createManual(
            TicketId::generate(),
            $input->employeeId,
            $input->taskId,
            $input->workId,
            $input->date,
            $input->hours,
            $input->comment,
            $rateSnapshot,
        );

        $this->ticketRepository->save($ticket);

        foreach ($ticket->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
