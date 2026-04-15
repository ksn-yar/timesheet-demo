<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\UpdateChangeRequestInputDto;
use App\ProjectManagement\Domain\Event\ChangeRequestUpdated;
use App\ProjectManagement\Domain\Exception\ChangeRequestNotFoundException;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Repository\ChangeRequestRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case обновления запроса на изменение. */
final class UpdateChangeRequestUseCase
{
    public function __construct(
        private readonly ChangeRequestRepositoryInterface $changeRequestRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateChangeRequestInputDto $input): void
    {
        $crId = new ChangeRequestId($input->id);
        $changeRequest = $this->changeRequestRepository->findById($crId);

        if (null === $changeRequest) {
            throw new ChangeRequestNotFoundException($input->id);
        }

        if ($changeRequest->isDeleted()) {
            throw new EntityDeletedException();
        }

        $changeRequest->update($input->name, $input->description);

        $this->changeRequestRepository->save($changeRequest);

        $this->eventDispatcher->dispatch(new ChangeRequestUpdated($crId));
    }
}
