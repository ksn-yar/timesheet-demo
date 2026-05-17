<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Dto\DeleteUserInputDto;
use App\Identity\Application\Port\TicketExistenceCheckerInterface;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\UserHasLinkedTicketsException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case мягкого удаления пользователя. */
final readonly class DeleteUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TicketExistenceCheckerInterface $ticketChecker,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DeleteUserInputDto $input): void
    {
        $userId = new UserId($input->userId);
        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            throw new UserNotFoundException($input->userId);
        }

        if ($user->isDeleted()) {
            throw new EntityDeletedException();
        }

        if ($this->ticketChecker->hasTicketsForUser($userId)) {
            throw new UserHasLinkedTicketsException($input->userId);
        }

        $user->softDelete();

        $this->userRepository->save($user);

        foreach ($user->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
