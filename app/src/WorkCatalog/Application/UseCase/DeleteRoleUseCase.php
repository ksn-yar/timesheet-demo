<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\UseCase;

use App\WorkCatalog\Application\Dto\DeleteRoleInputDto;
use App\WorkCatalog\Application\Port\UserExistenceByRoleCheckerInterface;
use App\WorkCatalog\Domain\Exception\EntityDeletedException;
use App\WorkCatalog\Domain\Exception\RoleHasActiveRatesException;
use App\WorkCatalog\Domain\Exception\RoleHasLinkedUsersException;
use App\WorkCatalog\Domain\Exception\RoleNotFoundException;
use App\WorkCatalog\Domain\Repository\RoleRepositoryInterface;
use App\WorkCatalog\Domain\ValueObject\RoleId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case удаления роли (soft delete). */
final readonly class DeleteRoleUseCase
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private UserExistenceByRoleCheckerInterface $userChecker,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DeleteRoleInputDto $input): void
    {
        $roleId = new RoleId($input->id);
        $role = $this->roleRepository->findById($roleId);

        if (null === $role) {
            throw new RoleNotFoundException($input->id);
        }

        if ($role->isDeleted()) {
            throw new EntityDeletedException();
        }

        $activeRates = $this->roleRepository->countActiveRatesByRoleId($roleId);

        if ($activeRates > 0) {
            throw new RoleHasActiveRatesException($input->id);
        }

        if ($this->userChecker->hasUsersByRole($roleId)) {
            throw new RoleHasLinkedUsersException($input->id);
        }

        $role->softDelete();

        $this->roleRepository->save($role);

        foreach ($role->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
