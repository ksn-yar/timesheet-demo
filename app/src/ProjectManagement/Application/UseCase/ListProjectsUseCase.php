<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\ListProjectsInputDto;
use App\ProjectManagement\Application\Dto\ListProjectsOutputDto;
use App\ProjectManagement\Application\Dto\ProjectItemDto;
use App\ProjectManagement\Application\Port\ListProjectsOutputPortInterface;
use App\ProjectManagement\Domain\Entity\Project;
use App\ProjectManagement\Domain\Repository\ClientRepositoryInterface;
use App\ProjectManagement\Domain\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ClientId;

/** Use Case получения списка проектов с пагинацией и фильтрацией. */
final readonly class ListProjectsUseCase
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private ClientRepositoryInterface $clientRepository,
        private ListProjectsOutputPortInterface $presenter,
    ) {}

    public function execute(ListProjectsInputDto $input): void
    {
        $criteria = [];

        if (null !== $input->clientId) {
            $criteria['clientId'] = $input->clientId;
        }

        if (null !== $input->status) {
            $criteria['status'] = $input->status;
        }

        $projects = $this->projectRepository->findAll($criteria, $input->page, $input->perPage);
        $total = $this->projectRepository->countAll($criteria);

        $clientNames = $this->resolveClientNames($projects);

        $items = array_map(
            static fn (Project $project): ProjectItemDto => new ProjectItemDto(
                id: $project->getId()->value(),
                clientId: $project->getClientId()->value(),
                clientName: $clientNames[$project->getClientId()->value()] ?? '',
                name: $project->getName(),
                status: $project->getStatus()->value,
                statusLabel: $project->getStatus()->getLabel(),
                description: $project->getDescription(),
            ),
            $projects,
        );

        $this->presenter->present(new ListProjectsOutputDto(
            items: $items,
            total: $total,
            page: $input->page,
            perPage: $input->perPage,
        ));
    }

    /**
     * Собирает имена клиентов для списка проектов одним проходом.
     *
     * @param Project[] $projects
     *
     * @return array<string, string>
     */
    private function resolveClientNames(array $projects): array
    {
        $clientIds = array_unique(array_map(
            static fn (Project $p): string => $p->getClientId()->value(),
            $projects,
        ));

        $names = [];

        foreach ($clientIds as $clientId) {
            $client = $this->clientRepository->findById(new ClientId($clientId));

            if (null !== $client) {
                $names[$clientId] = $client->getName();
            }
        }

        return $names;
    }
}
