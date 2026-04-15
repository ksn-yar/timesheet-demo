<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\UpdateProjectInputDto;
use App\ProjectManagement\Domain\Enum\ProjectStatus;
use App\ProjectManagement\Domain\Event\ProjectUpdated;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Exception\ProjectNotFoundException;
use App\ProjectManagement\Domain\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case обновления проекта. */
final class UpdateProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateProjectInputDto $input): void
    {
        $projectId = new ProjectId($input->id);
        $project = $this->projectRepository->findById($projectId);

        if (null === $project) {
            throw new ProjectNotFoundException($input->id);
        }

        if ($project->isDeleted()) {
            throw new EntityDeletedException();
        }

        $status = null !== $input->status ? ProjectStatus::from($input->status) : null;

        $project->update($input->name, $status, $input->description);

        $this->projectRepository->save($project);

        $this->eventDispatcher->dispatch(new ProjectUpdated($projectId));
    }
}
