<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\CreateChangeRequestInputDto;
use App\ProjectManagement\Domain\Entity\ChangeRequest;
use App\ProjectManagement\Domain\Event\ChangeRequestCreated;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Exception\ProjectNotFoundException;
use App\ProjectManagement\Domain\Repository\ChangeRequestRepositoryInterface;
use App\ProjectManagement\Domain\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case создания запроса на изменение. */
final readonly class CreateChangeRequestUseCase
{
    public function __construct(
        private ChangeRequestRepositoryInterface $changeRequestRepository,
        private ProjectRepositoryInterface $projectRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateChangeRequestInputDto $input): void
    {
        $projectId = new ProjectId($input->projectId);
        $project = $this->projectRepository->findById($projectId);

        if (null === $project) {
            throw new ProjectNotFoundException($input->projectId);
        }

        if ($project->isDeleted()) {
            throw new EntityDeletedException();
        }

        $changeRequest = ChangeRequest::create(
            $input->projectId,
            $input->name,
            $input->description,
        );

        $this->changeRequestRepository->save($changeRequest);

        $this->eventDispatcher->dispatch(new ChangeRequestCreated(
            new ChangeRequestId($changeRequest->getId()->value()),
            $projectId,
            $changeRequest->getName(),
        ));
    }
}
