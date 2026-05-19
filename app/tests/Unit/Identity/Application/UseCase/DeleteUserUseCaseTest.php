<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\UseCase;

use App\Identity\Application\Dto\DeleteUserInputDto;
use App\Identity\Application\Port\TicketExistenceCheckerInterface;
use App\Identity\Application\UseCase\DeleteUserUseCase;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\SystemRole;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\UserHasLinkedTicketsException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\Email;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Тесты Use Case мягкого удаления пользователя. */
final class DeleteUserUseCaseTest extends TestCase
{
    private const string USER_ID = '550e8400-e29b-41d4-a716-446655440001';

    #[Test]
    public function softDeletesUserAndDispatchesEvents(): void
    {
        $user = $this->buildActiveUser();

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $ticketChecker = $this->createMock(TicketExistenceCheckerInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $ticketChecker
            ->expects($this->once())
            ->method('hasTicketsForUser')
            ->willReturn(false);

        $userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch');

        $useCase = new DeleteUserUseCase($userRepository, $ticketChecker, $eventDispatcher);
        $useCase->execute(new DeleteUserInputDto(userId: self::USER_ID));
    }

    #[Test]
    public function throwsUserNotFoundExceptionWhenUserNotFound(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $userRepository
            ->expects($this->never())
            ->method('save');

        $useCase = new DeleteUserUseCase(
            $userRepository,
            $this->createStub(TicketExistenceCheckerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(UserNotFoundException::class);

        $useCase->execute(new DeleteUserInputDto(userId: self::USER_ID));
    }

    #[Test]
    public function throwsEntityDeletedExceptionWhenUserAlreadyDeleted(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->buildDeletedUser());

        $userRepository
            ->expects($this->never())
            ->method('save');

        $useCase = new DeleteUserUseCase(
            $userRepository,
            $this->createStub(TicketExistenceCheckerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(EntityDeletedException::class);

        $useCase->execute(new DeleteUserInputDto(userId: self::USER_ID));
    }

    #[Test]
    public function throwsUserHasLinkedTicketsExceptionWhenTicketsExist(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $ticketChecker = $this->createMock(TicketExistenceCheckerInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->buildActiveUser());

        $ticketChecker
            ->expects($this->once())
            ->method('hasTicketsForUser')
            ->willReturn(true);

        $userRepository
            ->expects($this->never())
            ->method('save');

        $useCase = new DeleteUserUseCase(
            $userRepository,
            $ticketChecker,
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(UserHasLinkedTicketsException::class);

        $useCase->execute(new DeleteUserInputDto(userId: self::USER_ID));
    }

    #[Test]
    public function neverCallsSaveWhenUserIsDeleted(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $userRepository
            ->method('findById')
            ->willReturn($this->buildDeletedUser());

        $userRepository
            ->expects($this->never())
            ->method('save');

        $useCase = new DeleteUserUseCase(
            $userRepository,
            $this->createStub(TicketExistenceCheckerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(EntityDeletedException::class);

        $useCase->execute(new DeleteUserInputDto(userId: self::USER_ID));
    }

    private function buildActiveUser(): User
    {
        return User::restore(
            new UserId(self::USER_ID),
            'Иван Иванов',
            new Email('ivan@example.com'),
            new HashedPassword('$2y$hash'),
            SystemRole::Employee,
            null,
            null,
            true,
            null,
        );
    }

    private function buildDeletedUser(): User
    {
        return User::restore(
            new UserId(self::USER_ID),
            'Иван Иванов',
            new Email('ivan@example.com'),
            new HashedPassword('$2y$hash'),
            SystemRole::Employee,
            null,
            null,
            false,
            new DateTimeImmutable(),
        );
    }
}
