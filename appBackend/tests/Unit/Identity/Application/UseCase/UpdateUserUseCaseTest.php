<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\UseCase;

use App\Identity\Application\Dto\UpdateUserInputDto;
use App\Identity\Application\UseCase\UpdateUserUseCase;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\SystemRole;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\Email;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Тесты Use Case обновления атрибутов пользователя.
 *
 * @internal
 *
 * @coversNothing
 */
final class UpdateUserUseCaseTest extends TestCase
{
    private const string USER_ID = '550e8400-e29b-41d4-a716-446655440001';

    #[Test]
    public function updatesUserAndDispatchesEvents(): void
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

        $useCase = new UpdateUserUseCase($userRepository, $eventDispatcher);
        $useCase->execute(new UpdateUserInputDto(
            userId: self::USER_ID,
            name: 'Новое имя',
            systemRole: 'employee',
            roleId: null,
        ));
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

        $useCase = new UpdateUserUseCase(
            $userRepository,
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(UserNotFoundException::class);

        $useCase->execute(new UpdateUserInputDto(
            userId: self::USER_ID,
            name: 'Новое имя',
            systemRole: 'employee',
            roleId: null,
        ));
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

        $useCase = new UpdateUserUseCase($userRepository, $eventDispatcher);
        $useCase->execute(new UpdateUserInputDto(
            userId: self::USER_ID,
            name: 'Новое имя',
            systemRole: 'employee',
            roleId: null,
        ));

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
}
