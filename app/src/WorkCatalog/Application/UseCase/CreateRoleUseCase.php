<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\UseCase;

use App\WorkCatalog\Application\Dto\CreateRoleInputDto;
use App\WorkCatalog\Domain\Entity\Role;
use App\WorkCatalog\Domain\Exception\DuplicateRoleNameException;
use App\WorkCatalog\Domain\Repository\RoleRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case создания новой роли. */
final class CreateRoleUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateRoleInputDto $input): void
    {
        $existing = $this->roleRepository->findByName($input->name);

        if (null !== $existing && !$existing->isDeleted()) {
            throw new DuplicateRoleNameException($input->name);
        }

        $role = Role::create($input->name, $input->description);

        $this->roleRepository->save($role);

        foreach ($role->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
