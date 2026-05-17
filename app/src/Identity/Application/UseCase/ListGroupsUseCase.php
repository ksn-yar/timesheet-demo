<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Dto\GroupItemDto;
use App\Identity\Application\Dto\ListGroupsInputDto;
use App\Identity\Application\Dto\ListGroupsOutputDto;
use App\Identity\Application\Port\ListGroupsOutputPortInterface;
use App\Identity\Domain\Entity\Group;
use App\Identity\Domain\Repository\GroupRepositoryInterface;

/** Use Case получения списка групп с пагинацией. */
final readonly class ListGroupsUseCase
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository,
        private ListGroupsOutputPortInterface $presenter,
    ) {}

    public function execute(ListGroupsInputDto $input): void
    {
        $criteria = [];

        $result = $this->groupRepository->findAll($criteria, $input->page, $input->perPage);

        $items = array_map(
            static fn (Group $group): GroupItemDto => new GroupItemDto(
                id: $group->getId()->value(),
                name: $group->getName(),
                description: $group->getDescription(),
            ),
            $result['items'],
        );

        $this->presenter->present(new ListGroupsOutputDto(
            items: $items,
            total: $result['total'],
            page: $input->page,
            perPage: $input->perPage,
        ));
    }
}
