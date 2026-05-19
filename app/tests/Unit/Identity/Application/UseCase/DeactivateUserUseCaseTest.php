<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\UseCase;

use App\Identity\Application\Dto\DeactivateUserInputDto;
use App\Identity\Application\UseCase\DeactivateUserUseCase;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\SystemRole;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\UserAlreadyDeactivatedException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\Email;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Тесты Use Case деактивации пользователя.
 *
 * @internal
 *
 * @coversNothing
 */
final class DeactivateUserUseCaseTest extends TestCase
{
    private const string USER_ID = '550e8400-e29b-41d4-a716-446655440001';

    #[Test]
    public function deactivatesActiveUserAndDispatchesEvents(): void
    {
        $user = $this->buildActiveUser();

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user)
        ;

        $userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user)
        ;

        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
        ;

        $useCase = new DeactivateUserUseCase($userRepository, $eventDispatcher);
        $useCase->execute(new DeactivateUserInputDto(userId: self::USER_ID));
    }

    #[Test]
    public function throwsUserNotFoundExceptionWhenUserNotFound(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null)
        ;

        $userRepository
            ->expects($this->never())
            ->method('save')
        ;

        $useCase = new DeactivateUserUseCase(
            $userRepository,
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(UserNotFoundException::class);

        $useCase->execute(new DeactivateUserInputDto(userId: self::USER_ID));
    }

    #[Test]
    public function throwsEntityDeletedExceptionWhenUserIsDeleted(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->buildDeletedUser())
        ;

        $userRepository
            ->expects($this->never())
            ->method('save')
        ;

        $useCase = new DeactivateUserUseCase(
            $userRepository,
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(EntityDeletedException::class);

        $useCase->execute(new DeactivateUserInputDto(userId: self::USER_ID));
    }

    #[Test]
    public function throwsUserAlreadyDeactivatedExceptionWhenUserIsInactive(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->buildInactiveUser())
        ;

        $userRepository
            ->expects($this->never())
            ->method('save')
        ;

        $useCase = new DeactivateUserUseCase(
            $userRepository,
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(UserAlreadyDeactivatedException::class);

        $useCase->execute(new DeactivateUserInputDto(userId: self::USER_ID));
    }

    #[Test]
    public function dispatchesEventsAfterSave(): void
    {
        $user = $this->buildActiveUser();

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($user);

        $saveCallOrder = 0;
        $dispatchCallOrder = 0;

        $userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(static function () use (&$saveCallOrder): void {
                $saveCallOrder = 1;
            })
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(function (object $event) use (&$saveCallOrder, &$dispatchCallOrder): object {
                $this->assertGreaterThan(0, $saveCallOrder, 'dispatch() вызван до save()');
                ++$dispatchCallOrder;

                return $event;
            })
        ;

        $useCase = new DeactivateUserUseCase($userRepository, $eventDispatcher);
        $useCase->execute(new DeactivateUserInputDto(userId: self::USER_ID));

        $this->assertGreaterThan(0, $dispatchCallOrder, 'dispatch() не был вызван ни разу');
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

    private function buildInactiveUser(): User
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
