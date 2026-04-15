<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Dto\CreateUserInputDto;
use App\Identity\Application\Port\PasswordHasherInterface;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\SystemRole;
use App\Identity\Domain\Exception\DuplicateEmailException;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Domain\Repository\GroupRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\Email;
use App\Identity\Domain\ValueObject\GroupId;
use App\Identity\Domain\ValueObject\UserId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case создания нового пользователя. */
final class CreateUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateUserInputDto $input): void
    {
        $email = new Email($input->email);

        if ($this->userRepository->existsByEmail($email)) {
            throw new DuplicateEmailException($input->email);
        }

        $groupId = null;

        if (null !== $input->groupId) {
            $groupId = new GroupId($input->groupId);
            $group = $this->groupRepository->findById($groupId);

            if (null === $group) {
                throw new GroupNotFoundException($input->groupId);
            }

            if ($group->isDeleted()) {
                throw new EntityDeletedException();
            }
        }

        $hashedPassword = $this->passwordHasher->hash($input->password);

        $user = User::create(
            UserId::generate(),
            $input->name,
            $email,
            $hashedPassword,
            SystemRole::from($input->systemRole),
            $groupId,
            $input->roleId,
        );

        $this->userRepository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
