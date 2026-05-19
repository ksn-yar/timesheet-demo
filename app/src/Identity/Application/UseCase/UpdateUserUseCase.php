<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Dto\UpdateUserInputDto;
use App\Identity\Application\Port\UpdateUserUseCaseInterface;
use App\Identity\Domain\Enum\SystemRole;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case обновления атрибутов пользователя. */
final readonly class UpdateUserUseCase implements UpdateUserUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateUserInputDto $input): void
    {
        $userId = new UserId($input->userId);
        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            throw new UserNotFoundException($input->userId);
        }

        $user->update(
            $input->name,
            SystemRole::from($input->systemRole),
            $input->roleId,
        );

        $this->userRepository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
