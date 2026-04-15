<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\ListTasksInputDto;
use App\ProjectManagement\Application\Dto\ListTasksOutputDto;
use App\ProjectManagement\Application\Dto\TaskItemDto;
use App\ProjectManagement\Application\Port\ListTasksOutputPortInterface;
use App\ProjectManagement\Domain\Entity\Task;
use App\ProjectManagement\Domain\Repository\ChangeRequestRepositoryInterface;
use App\ProjectManagement\Domain\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Domain\Repository\TaskRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;
use App\ProjectManagement\Domain\ValueObject\ProjectId;

/** Use Case получения списка задач с пагинацией и фильтрацией. */
final class ListTasksUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly ChangeRequestRepositoryInterface $changeRequestRepository,
        private readonly ListTasksOutputPortInterface $presenter,
    ) {}

    public function execute(ListTasksInputDto $input): void
    {
        $criteria = [];

        if (null !== $input->projectId) {
            $criteria['projectId'] = $input->projectId;
        }

        if (null !== $input->crId) {
            $criteria['crId'] = $input->crId;
        }

        $tasks = $this->taskRepository->findAll($criteria, $input->page, $input->perPage);
        $total = $this->taskRepository->countAll($criteria);

        [$projectNames, $crNames] = $this->resolveParentNames($tasks);

        $items = array_map(
            static function (Task $task) use ($projectNames, $crNames): TaskItemDto {
                $projectId = $task->getProjectId()?->value();
                $crId = $task->getCrId()?->value();

                return new TaskItemDto(
                    id: $task->getId()->value(),
                    name: $task->getName(),
                    projectId: $projectId,
                    projectName: null !== $projectId ? ($projectNames[$projectId] ?? '') : null,
                    crId: $crId,
                    crName: null !== $crId ? ($crNames[$crId] ?? '') : null,
                    description: $task->getDescription(),
                    estimate: $task->getEstimate()?->value(),
                );
            },
            $tasks,
        );

        $this->presenter->present(new ListTasksOutputDto(
            items: $items,
            total: $total,
            page: $input->page,
            perPage: $input->perPage,
        ));
    }

    /**
     * Собирает имена проектов и запросов на изменение для списка задач.
     *
     * @param Task[] $tasks
     *
     * @return array{array<string, string>, array<string, string>}
     */
    private function resolveParentNames(array $tasks): array
    {
        $projectIds = [];
        $crIds = [];

        foreach ($tasks as $task) {
            if (null !== $task->getProjectId()) {
                $projectIds[] = $task->getProjectId()->value();
            }

            if (null !== $task->getCrId()) {
                $crIds[] = $task->getCrId()->value();
            }
        }

        $projectNames = [];

        foreach (array_unique($projectIds) as $projectId) {
            $project = $this->projectRepository->findById(new ProjectId($projectId));

            if (null !== $project) {
                $projectNames[$projectId] = $project->getName();
            }
        }

        $crNames = [];

        foreach (array_unique($crIds) as $crId) {
            $cr = $this->changeRequestRepository->findById(new ChangeRequestId($crId));

            if (null !== $cr) {
                $crNames[$crId] = $cr->getName();
            }
        }

        return [$projectNames, $crNames];
    }
}
