<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\ChangeRequestItemDto;
use App\ProjectManagement\Application\Dto\ListChangeRequestsInputDto;
use App\ProjectManagement\Application\Dto\ListChangeRequestsOutputDto;
use App\ProjectManagement\Application\Port\ListChangeRequestsOutputPortInterface;
use App\ProjectManagement\Domain\Entity\ChangeRequest;
use App\ProjectManagement\Domain\Repository\ChangeRequestRepositoryInterface;
use App\ProjectManagement\Domain\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ProjectId;

/** Use Case получения списка запросов на изменение с пагинацией и фильтрацией. */
final class ListChangeRequestsUseCase
{
    public function __construct(
        private readonly ChangeRequestRepositoryInterface $changeRequestRepository,
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly ListChangeRequestsOutputPortInterface $presenter,
    ) {}

    public function execute(ListChangeRequestsInputDto $input): void
    {
        $criteria = [];

        if (null !== $input->projectId) {
            $criteria['projectId'] = $input->projectId;
        }

        if (null !== $input->name) {
            $criteria['name'] = $input->name;
        }

        $changeRequests = $this->changeRequestRepository->findAll(
            $criteria,
            $input->page,
            $input->perPage,
        );
        $total = $this->changeRequestRepository->countAll($criteria);

        $projectNames = $this->resolveProjectNames($changeRequests);

        $items = array_map(
            static fn (ChangeRequest $cr): ChangeRequestItemDto => new ChangeRequestItemDto(
                id: $cr->getId()->value(),
                projectId: $cr->getProjectId()->value(),
                projectName: $projectNames[$cr->getProjectId()->value()] ?? '',
                name: $cr->getName(),
                description: $cr->getDescription(),
            ),
            $changeRequests,
        );

        $this->presenter->present(new ListChangeRequestsOutputDto(
            items: $items,
            total: $total,
            page: $input->page,
            perPage: $input->perPage,
        ));
    }

    /**
     * Собирает имена проектов для списка запросов на изменение одним проходом.
     *
     * @param ChangeRequest[] $changeRequests
     *
     * @return array<string, string>
     */
    private function resolveProjectNames(array $changeRequests): array
    {
        $projectIds = array_unique(array_map(
            static fn (ChangeRequest $cr): string => $cr->getProjectId()->value(),
            $changeRequests,
        ));

        $names = [];

        foreach ($projectIds as $projectId) {
            $project = $this->projectRepository->findById(new ProjectId($projectId));

            if (null !== $project) {
                $names[$projectId] = $project->getName();
            }
        }

        return $names;
    }
}
