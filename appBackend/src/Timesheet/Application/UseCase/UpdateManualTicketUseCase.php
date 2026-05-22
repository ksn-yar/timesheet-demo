<?php

declare(strict_types=1);

namespace App\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\UpdateManualTicketInputDto;
use App\Timesheet\Application\Port\CurrentUserProviderInterface;
use App\Timesheet\Application\Port\WorkExistenceCheckerInterface;
use App\Timesheet\Domain\Exception\TicketNotFoundException;
use App\Timesheet\Domain\Exception\TicketOwnershipViolationException;
use App\Timesheet\Domain\Repository\TicketRepositoryInterface;
use App\Timesheet\Domain\ValueObject\TicketId;
use DomainException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case обновления тикета с проверкой прав владения и редактируемости. */
final readonly class UpdateManualTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepository,
        private WorkExistenceCheckerInterface $workExistenceChecker,
        private CurrentUserProviderInterface $currentUserProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateManualTicketInputDto $input): void
    {
        $ticket = $this->ticketRepository->findById(new TicketId($input->ticketId));

        if (null === $ticket) {
            throw new TicketNotFoundException($input->ticketId);
        }

        $currentUserId = $this->currentUserProvider->getCurrentUserId();

        if (!$this->currentUserProvider->isAdmin() && $ticket->getEmployeeId() !== $currentUserId) {
            throw new TicketOwnershipViolationException();
        }

        if (null !== $input->workId && !$this->workExistenceChecker->workExists($input->workId)) {
            throw new DomainException("Вид работ с идентификатором «{$input->workId}» не найден.");
        }

        $ticket->update($input->date, $input->hours, $input->workId, $input->comment);

        $this->ticketRepository->save($ticket);

        foreach ($ticket->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
