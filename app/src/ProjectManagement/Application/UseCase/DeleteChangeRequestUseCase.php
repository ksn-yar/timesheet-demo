<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\DeleteChangeRequestInputDto;
use App\ProjectManagement\Domain\Event\ChangeRequestDeleted;
use App\ProjectManagement\Domain\Exception\ChangeRequestHasLinkedTasksException;
use App\ProjectManagement\Domain\Exception\ChangeRequestNotFoundException;
use App\ProjectManagement\Domain\Repository\ChangeRequestRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case удаления запроса на изменение (soft delete). */
final readonly class DeleteChangeRequestUseCase
{
    public function __construct(
        private ChangeRequestRepositoryInterface $changeRequestRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DeleteChangeRequestInputDto $input): void
    {
        $crId = new ChangeRequestId($input->id);
        $changeRequest = $this->changeRequestRepository->findById($crId);

        if (null === $changeRequest || $changeRequest->isDeleted()) {
            throw new ChangeRequestNotFoundException($input->id);
        }

        $activeTasks = $this->changeRequestRepository->countActiveTasksByChangeRequestId($crId);

        if ($activeTasks > 0) {
            throw new ChangeRequestHasLinkedTasksException($input->id);
        }

        $changeRequest->softDelete();

        $this->changeRequestRepository->save($changeRequest);

        $this->eventDispatcher->dispatch(new ChangeRequestDeleted($crId));
    }
}
