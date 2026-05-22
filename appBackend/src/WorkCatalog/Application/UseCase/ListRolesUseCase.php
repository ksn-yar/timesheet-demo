<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\UseCase;

use App\WorkCatalog\Application\Dto\ListRolesInputDto;
use App\WorkCatalog\Application\Dto\ListRolesOutputDto;
use App\WorkCatalog\Application\Dto\RoleItemDto;
use App\WorkCatalog\Application\Port\ListRolesOutputPortInterface;
use App\WorkCatalog\Domain\Entity\Role;
use App\WorkCatalog\Domain\Repository\RoleRepositoryInterface;

/** Use Case получения списка ролей с пагинацией. */
final readonly class ListRolesUseCase
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private ListRolesOutputPortInterface $presenter,
    ) {}

    public function execute(ListRolesInputDto $input): void
    {
        $criteria = [];
        $offset = ($input->page - 1) * $input->perPage;

        $roles = $this->roleRepository->findAll($criteria, $input->perPage, $offset);
        $total = $this->roleRepository->countAll($criteria);

        $items = array_map(
            static fn (Role $role): RoleItemDto => new RoleItemDto(
                id: $role->getId()->value(),
                name: $role->getName(),
                description: $role->getDescription(),
            ),
            $roles,
        );

        $this->presenter->present(new ListRolesOutputDto(
            items: $items,
            total: $total,
            page: $input->page,
            perPage: $input->perPage,
        ));
    }
}
