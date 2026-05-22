<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Dto\ListUsersInputDto;
use App\Identity\Application\Dto\ListUsersOutputDto;
use App\Identity\Application\Dto\UserItemDto;
use App\Identity\Application\Port\ListUsersOutputPortInterface;
use App\Identity\Application\Port\ListUsersUseCaseInterface;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;

/** Use Case получения списка пользователей с пагинацией и фильтрацией. */
final readonly class ListUsersUseCase implements ListUsersUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private ListUsersOutputPortInterface $presenter,
    ) {}

    public function execute(ListUsersInputDto $input): void
    {
        $criteria = [];

        if (null !== $input->groupId) {
            $criteria['groupId'] = $input->groupId;
        }

        if (null !== $input->roleId) {
            $criteria['roleId'] = $input->roleId;
        }

        if (null !== $input->isActive) {
            $criteria['isActive'] = $input->isActive;
        }

        $result = $this->userRepository->findAll($criteria, $input->page, $input->perPage);

        $items = array_map(
            static fn (User $user): UserItemDto => new UserItemDto(
                id: $user->getId()->value(),
                name: $user->getName(),
                email: $user->getEmail()->value(),
                systemRole: $user->getSystemRole()->value,
                groupId: $user->getGroupId()?->value(),
                groupName: null,
                roleId: $user->getRoleId(),
                roleName: null,
                isActive: $user->isActive(),
            ),
            $result['items'],
        );

        $this->presenter->present(new ListUsersOutputDto(
            items: $items,
            total: $result['total'],
            page: $input->page,
            perPage: $input->perPage,
        ));
    }
}
