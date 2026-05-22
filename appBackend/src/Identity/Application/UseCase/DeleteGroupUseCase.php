<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Dto\DeleteGroupInputDto;
use App\Identity\Application\Port\DeleteGroupUseCaseInterface;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupHasActiveUsersException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Domain\Repository\GroupRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\GroupId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case мягкого удаления группы. */
final readonly class DeleteGroupUseCase implements DeleteGroupUseCaseInterface
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository,
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DeleteGroupInputDto $input): void
    {
        $groupId = new GroupId($input->groupId);
        $group = $this->groupRepository->findById($groupId);

        if (null === $group) {
            throw new GroupNotFoundException($input->groupId);
        }

        if ($group->isDeleted()) {
            throw new EntityDeletedException();
        }

        $activeUsers = $this->userRepository->countActiveUsersByGroupId($groupId);

        if ($activeUsers > 0) {
            throw new GroupHasActiveUsersException($input->groupId);
        }

        $group->softDelete();

        $this->groupRepository->save($group);

        foreach ($group->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
