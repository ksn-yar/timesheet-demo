<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Dto\ChangeUserGroupInputDto;
use App\Identity\Application\Port\ChangeUserGroupUseCaseInterface;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Domain\Repository\GroupRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\GroupId;
use App\Identity\Domain\ValueObject\UserId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case изменения принадлежности пользователя к группе. Объединяет назначение и снятие. */
final readonly class ChangeUserGroupUseCase implements ChangeUserGroupUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private GroupRepositoryInterface $groupRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(ChangeUserGroupInputDto $input): void
    {
        $userId = new UserId($input->userId);
        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            throw new UserNotFoundException($input->userId);
        }

        if ($user->isDeleted()) {
            throw new EntityDeletedException();
        }

        if (null !== $input->groupId) {
            $groupId = new GroupId($input->groupId);
            $group = $this->groupRepository->findById($groupId);

            if (null === $group) {
                throw new GroupNotFoundException($input->groupId);
            }

            if ($group->isDeleted()) {
                throw new EntityDeletedException();
            }

            $user->assignToGroup($groupId);
        } else {
            $user->removeFromGroup();
        }

        $this->userRepository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
