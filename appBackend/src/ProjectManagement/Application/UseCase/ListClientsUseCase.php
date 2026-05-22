<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\ClientItemDto;
use App\ProjectManagement\Application\Dto\ListClientsInputDto;
use App\ProjectManagement\Application\Dto\ListClientsOutputDto;
use App\ProjectManagement\Application\Port\ListClientsOutputPortInterface;
use App\ProjectManagement\Domain\Entity\Client;
use App\ProjectManagement\Domain\Repository\ClientRepositoryInterface;

/** Use Case получения списка клиентов с пагинацией и фильтрацией. */
final readonly class ListClientsUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private ListClientsOutputPortInterface $presenter,
    ) {}

    public function execute(ListClientsInputDto $input): void
    {
        $criteria = [];

        if (null !== $input->name) {
            $criteria['name'] = $input->name;
        }

        $clients = $this->clientRepository->findAll($criteria, $input->page, $input->perPage);
        $total = $this->clientRepository->countAll($criteria);

        $items = array_map(
            static fn (Client $client): ClientItemDto => new ClientItemDto(
                id: $client->getId()->value(),
                name: $client->getName(),
                description: $client->getDescription(),
            ),
            $clients,
        );

        $this->presenter->present(new ListClientsOutputDto(
            items: $items,
            total: $total,
            page: $input->page,
            perPage: $input->perPage,
        ));
    }
}
