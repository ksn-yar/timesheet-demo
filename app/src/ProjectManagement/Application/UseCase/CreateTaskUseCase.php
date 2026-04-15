<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\CreateTaskInputDto;
use App\ProjectManagement\Domain\Entity\Task;
use App\ProjectManagement\Domain\Event\TaskCreated;
use App\ProjectManagement\Domain\Exception\ChangeRequestNotFoundException;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Exception\InvalidTaskParentException;
use App\ProjectManagement\Domain\Exception\ProjectNotFoundException;
use App\ProjectManagement\Domain\Repository\ChangeRequestRepositoryInterface;
use App\ProjectManagement\Domain\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Domain\Repository\TaskRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\ProjectManagement\Domain\ValueObject\TaskId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case создания задачи, привязанной к проекту или запросу на изменение. */
final class CreateTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly ChangeRequestRepositoryInterface $changeRequestRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateTaskInputDto $input): void
    {
        $hasProject = null !== $input->projectId;
        $hasCr = null !== $input->crId;

        if ($hasProject === $hasCr) {
            throw new InvalidTaskParentException();
        }

        if ($hasProject) {
            $project = $this->projectRepository->findById(new ProjectId($input->projectId));

            if (null === $project) {
                throw new ProjectNotFoundException($input->projectId);
            }

            if ($project->isDeleted()) {
                throw new EntityDeletedException();
            }

            $task = Task::createForProject(
                $input->projectId,
                $input->name,
                $input->description,
                $input->estimate,
            );
        } else {
            $changeRequest = $this->changeRequestRepository->findById(
                new ChangeRequestId($input->crId),
            );

            if (null === $changeRequest) {
                throw new ChangeRequestNotFoundException($input->crId);
            }

            if ($changeRequest->isDeleted()) {
                throw new EntityDeletedException();
            }

            $task = Task::createForChangeRequest(
                $input->crId,
                $input->name,
                $input->description,
                $input->estimate,
            );
        }

        $this->taskRepository->save($task);

        $this->eventDispatcher->dispatch(new TaskCreated(
            new TaskId($task->getId()->value()),
            $task->getName(),
        ));
    }
}
