<?php

declare(strict_types=1);

namespace App\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\GetTicketInputDto;
use App\Timesheet\Application\Port\CurrentUserProviderInterface;
use App\Timesheet\Domain\Entity\Ticket;
use App\Timesheet\Domain\Exception\TicketNotFoundException;
use App\Timesheet\Domain\Exception\TicketOwnershipViolationException;
use App\Timesheet\Domain\Repository\TicketRepositoryInterface;
use App\Timesheet\Domain\ValueObject\TicketId;

/** Use Case получения тикета по идентификатору с проверкой прав доступа. */
final class GetTicketUseCase
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepository,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {}

    public function execute(GetTicketInputDto $input): Ticket
    {
        $ticket = $this->ticketRepository->findById(new TicketId($input->ticketId));

        if (null === $ticket) {
            throw new TicketNotFoundException($input->ticketId);
        }

        if ($this->currentUserProvider->isEmployee()) {
            $currentUserId = $this->currentUserProvider->getCurrentUserId();

            if ($ticket->getEmployeeId() !== $currentUserId) {
                throw new TicketOwnershipViolationException();
            }
        }

        return $ticket;
    }
}
