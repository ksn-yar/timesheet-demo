<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\UpdateTaskInputDto;
use App\ProjectManagement\Domain\Event\TaskUpdated;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Exception\TaskNotFoundException;
use App\ProjectManagement\Domain\Repository\TaskRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\TaskId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case обновления задачи. */
final class UpdateTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateTaskInputDto $input): void
    {
        $taskId = new TaskId($input->id);
        $task = $this->taskRepository->findById($taskId);

        if (null === $task) {
            throw new TaskNotFoundException($input->id);
        }

        if ($task->isDeleted()) {
            throw new EntityDeletedException();
        }

        $task->update($input->name, $input->description, $input->estimate);

        $this->taskRepository->save($task);

        $this->eventDispatcher->dispatch(new TaskUpdated($taskId));
    }
}
