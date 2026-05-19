<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\UseCase;

use App\Identity\Application\Dto\ChangeUserGroupInputDto;
use App\Identity\Application\UseCase\ChangeUserGroupUseCase;
use App\Identity\Domain\Entity\Group;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\SystemRole;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Domain\Repository\GroupRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\Email;
use App\Identity\Domain\ValueObject\GroupId;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Тесты Use Case изменения принадлежности пользователя к группе. */
final class ChangeUserGroupUseCaseTest extends TestCase
{
    private const string USER_ID = '550e8400-e29b-41d4-a716-446655440001';
    private const string GROUP_ID = '550e8400-e29b-41d4-a716-446655440002';

    #[Test]
    public function assignsUserToGroupWhenBothExistAndNotDeleted(): void
    {
        $user = $this->buildActiveUser();
        $group = $this->buildActiveGroup();

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($group);

        $userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch');

        $useCase = new ChangeUserGroupUseCase($userRepository, $groupRepository, $eventDispatcher);
        $useCase->execute(new ChangeUserGroupInputDto(self::USER_ID, self::GROUP_ID));
    }

    #[Test]
    public function removesUserFromGroupWhenGroupIdIsNull(): void
    {
        $user = $this->buildActiveUser();

        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $useCase = new ChangeUserGroupUseCase(
            $userRepository,
            $this->createStub(GroupRepositoryInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $useCase->execute(new ChangeUserGroupInputDto(self::USER_ID, null));
    }

    #[Test]
    public function doesNotCallGroupRepositoryWhenGroupIdIsNull(): void
    {
        $user = $this->buildActiveUser();

        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($user);

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository
            ->expects($this->never())
            ->method('findById');

        $useCase = new ChangeUserGroupUseCase(
            $userRepository,
            $groupRepository,
            $this->createStub(EventDispatcherInterface::class),
        );

        $useCase->execute(new ChangeUserGroupInputDto(self::USER_ID, null));
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

        $useCase = new ChangeUserGroupUseCase(
            $userRepository,
            $this->createStub(GroupRepositoryInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(UserNotFoundException::class);

        $useCase->execute(new ChangeUserGroupInputDto(self::USER_ID, self::GROUP_ID));
    }

    #[Test]
    public function throwsEntityDeletedExceptionWhenUserIsDeleted(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->buildDeletedUser());

        $userRepository
            ->expects($this->never())
            ->method('save');

        $useCase = new ChangeUserGroupUseCase(
            $userRepository,
            $this->createStub(GroupRepositoryInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(EntityDeletedException::class);

        $useCase->execute(new ChangeUserGroupInputDto(self::USER_ID, self::GROUP_ID));
    }

    #[Test]
    public function throwsGroupNotFoundExceptionWhenGroupNotFound(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($this->buildActiveUser());
        $userRepository
            ->expects($this->never())
            ->method('save');

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $useCase = new ChangeUserGroupUseCase(
            $userRepository,
            $groupRepository,
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(GroupNotFoundException::class);

        $useCase->execute(new ChangeUserGroupInputDto(self::USER_ID, self::GROUP_ID));
    }

    #[Test]
    public function throwsEntityDeletedExceptionWhenGroupIsDeleted(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($this->buildActiveUser());
        $userRepository
            ->expects($this->never())
            ->method('save');

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->buildDeletedGroup());

        $useCase = new ChangeUserGroupUseCase(
            $userRepository,
            $groupRepository,
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(EntityDeletedException::class);

        $useCase->execute(new ChangeUserGroupInputDto(self::USER_ID, self::GROUP_ID));
    }

    #[Test]
    public function dispatchesDomainEventsAfterSave(): void
    {
        $user = $this->buildActiveUser();
        $group = $this->buildActiveGroup();

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($user);

        $groupRepository = $this->createStub(GroupRepositoryInterface::class);
        $groupRepository->method('findById')->willReturn($group);

        $saveCallOrder = 0;
        $dispatchCallOrder = 0;

        $userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function () use (&$saveCallOrder): void {
                $saveCallOrder = 1;
            });

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(function (object $event) use (&$saveCallOrder, &$dispatchCallOrder): object {
                // dispatch должен вызываться после save
                $this->assertGreaterThan(0, $saveCallOrder, 'dispatch() вызван до save()');
                $dispatchCallOrder++;

                return $event;
            });

        $useCase = new ChangeUserGroupUseCase($userRepository, $groupRepository, $eventDispatcher);
        $useCase->execute(new ChangeUserGroupInputDto(self::USER_ID, self::GROUP_ID));

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

    private function buildActiveGroup(): Group
    {
        return Group::restore(
            new GroupId(self::GROUP_ID),
            'Отдел разработки',
            null,
            null,
        );
    }

    private function buildDeletedGroup(): Group
    {
        return Group::restore(
            new GroupId(self::GROUP_ID),
            'Отдел разработки',
            null,
            new DateTimeImmutable(),
        );
    }
}
