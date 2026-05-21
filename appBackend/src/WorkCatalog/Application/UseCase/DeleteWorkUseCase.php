<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\UseCase;

use App\WorkCatalog\Application\Dto\DeleteWorkInputDto;
use App\WorkCatalog\Application\Port\TicketExistenceByWorkCheckerInterface;
use App\WorkCatalog\Domain\Exception\EntityDeletedException;
use App\WorkCatalog\Domain\Exception\WorkHasLinkedTicketsException;
use App\WorkCatalog\Domain\Exception\WorkNotFoundException;
use App\WorkCatalog\Domain\Repository\WorkRepositoryInterface;
use App\WorkCatalog\Domain\ValueObject\WorkId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case удаления вида работ (soft delete). */
final readonly class DeleteWorkUseCase
{
    public function __construct(
        private WorkRepositoryInterface $workRepository,
        private TicketExistenceByWorkCheckerInterface $ticketChecker,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DeleteWorkInputDto $input): void
    {
        $workId = new WorkId($input->id);
        $work = $this->workRepository->findById($workId);

        if (null === $work) {
            throw new WorkNotFoundException($input->id);
        }

        if ($work->isDeleted()) {
            throw new EntityDeletedException();
        }

        if ($this->ticketChecker->hasTicketsForWork($workId)) {
            throw new WorkHasLinkedTicketsException($input->id);
        }

        $work->softDelete();

        $this->workRepository->save($work);

        foreach ($work->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
