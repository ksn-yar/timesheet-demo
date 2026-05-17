<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\DeleteTaskInputDto;
use App\ProjectManagement\Domain\Event\TaskDeleted;
use App\ProjectManagement\Domain\Exception\TaskHasLinkedTicketsException;
use App\ProjectManagement\Domain\Exception\TaskNotFoundException;
use App\ProjectManagement\Domain\Repository\TaskRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\TaskId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case удаления задачи (soft delete). */
final readonly class DeleteTaskUseCase
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DeleteTaskInputDto $input): void
    {
        $taskId = new TaskId($input->id);
        $task = $this->taskRepository->findById($taskId);

        if (null === $task || $task->isDeleted()) {
            throw new TaskNotFoundException($input->id);
        }

        $hasTickets = $this->taskRepository->hasTicketsForTask($taskId);

        if ($hasTickets) {
            throw new TaskHasLinkedTicketsException($input->id);
        }

        $task->softDelete();

        $this->taskRepository->save($task);

        $this->eventDispatcher->dispatch(new TaskDeleted($taskId));
    }
}
