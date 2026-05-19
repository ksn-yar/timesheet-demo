<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Dto\CreateGroupInputDto;
use App\Identity\Application\Port\CreateGroupUseCaseInterface;
use App\Identity\Domain\Entity\Group;
use App\Identity\Domain\Exception\DuplicateGroupNameException;
use App\Identity\Domain\Repository\GroupRepositoryInterface;
use App\Identity\Domain\ValueObject\GroupId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case создания новой группы. */
final readonly class CreateGroupUseCase implements CreateGroupUseCaseInterface
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateGroupInputDto $input): void
    {
        if ($this->groupRepository->existsByName($input->name)) {
            throw new DuplicateGroupNameException($input->name);
        }

        $group = Group::create(
            GroupId::generate(),
            $input->name,
            $input->description,
        );

        $this->groupRepository->save($group);

        foreach ($group->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
