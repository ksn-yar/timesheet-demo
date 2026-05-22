<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Dto\UpdateGroupInputDto;
use App\Identity\Application\Port\UpdateGroupUseCaseInterface;
use App\Identity\Domain\Exception\DuplicateGroupNameException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Domain\Repository\GroupRepositoryInterface;
use App\Identity\Domain\ValueObject\GroupId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case обновления атрибутов группы. */
final readonly class UpdateGroupUseCase implements UpdateGroupUseCaseInterface
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateGroupInputDto $input): void
    {
        $groupId = new GroupId($input->groupId);
        $group = $this->groupRepository->findById($groupId);

        if (null === $group) {
            throw new GroupNotFoundException($input->groupId);
        }

        if ($group->getName() !== $input->name && $this->groupRepository->existsByName($input->name)) {
            throw new DuplicateGroupNameException($input->name);
        }

        $group->update($input->name, $input->description);

        $this->groupRepository->save($group);

        foreach ($group->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
