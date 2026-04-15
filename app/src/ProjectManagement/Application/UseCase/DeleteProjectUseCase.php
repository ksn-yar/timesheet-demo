<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\DeleteProjectInputDto;
use App\ProjectManagement\Domain\Event\ProjectDeleted;
use App\ProjectManagement\Domain\Exception\ProjectHasLinkedEntitiesException;
use App\ProjectManagement\Domain\Exception\ProjectNotFoundException;
use App\ProjectManagement\Domain\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case удаления проекта (soft delete). */
final class DeleteProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DeleteProjectInputDto $input): void
    {
        $projectId = new ProjectId($input->id);
        $project = $this->projectRepository->findById($projectId);

        if (null === $project || $project->isDeleted()) {
            throw new ProjectNotFoundException($input->id);
        }

        $activeTasks = $this->projectRepository->countActiveTasksByProjectId($projectId);
        $activeCrs = $this->projectRepository->countActiveChangeRequestsByProjectId($projectId);

        if ($activeTasks > 0 || $activeCrs > 0) {
            throw new ProjectHasLinkedEntitiesException($input->id);
        }

        $project->softDelete();

        $this->projectRepository->save($project);

        $this->eventDispatcher->dispatch(new ProjectDeleted($projectId));
    }
}
