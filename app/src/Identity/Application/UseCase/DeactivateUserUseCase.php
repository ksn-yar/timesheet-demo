<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Dto\DeactivateUserInputDto;
use App\Identity\Application\Port\DeactivateUserUseCaseInterface;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case деактивации пользователя. */
final readonly class DeactivateUserUseCase implements DeactivateUserUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DeactivateUserInputDto $input): void
    {
        $userId = new UserId($input->userId);
        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            throw new UserNotFoundException($input->userId);
        }

        $user->deactivate();

        $this->userRepository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
