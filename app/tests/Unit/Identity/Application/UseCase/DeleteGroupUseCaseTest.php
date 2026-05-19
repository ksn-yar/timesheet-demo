<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\UseCase;

use App\Identity\Application\Dto\DeleteGroupInputDto;
use App\Identity\Application\UseCase\DeleteGroupUseCase;
use App\Identity\Domain\Entity\Group;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupHasActiveUsersException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Domain\Repository\GroupRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\GroupId;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Тесты Use Case мягкого удаления группы. */
final class DeleteGroupUseCaseTest extends TestCase
{
    private const string GROUP_ID = '550e8400-e29b-41d4-a716-446655440002';

    #[Test]
    public function softDeletesGroupWhenNoActiveUsers(): void
    {
        $group = $this->buildActiveGroup();

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($group);

        $userRepository
            ->expects($this->once())
            ->method('countActiveUsersByGroupId')
            ->willReturn(0);

        $groupRepository
            ->expects($this->once())
            ->method('save')
            ->with($group);

        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch');

        $useCase = new DeleteGroupUseCase($groupRepository, $userRepository, $eventDispatcher);
        $useCase->execute(new DeleteGroupInputDto(groupId: self::GROUP_ID));
    }

    #[Test]
    public function throwsGroupNotFoundExceptionWhenGroupNotFound(): void
    {
        $groupRepository = $this->createMock(GroupRepositoryInterface::class);

        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $groupRepository
            ->expects($this->never())
            ->method('save');

        $useCase = new DeleteGroupUseCase(
            $groupRepository,
            $this->createStub(UserRepositoryInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(GroupNotFoundException::class);

        $useCase->execute(new DeleteGroupInputDto(groupId: self::GROUP_ID));
    }

    #[Test]
    public function throwsEntityDeletedExceptionWhenGroupAlreadyDeleted(): void
    {
        $groupRepository = $this->createMock(GroupRepositoryInterface::class);

        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->buildDeletedGroup());

        $groupRepository
            ->expects($this->never())
            ->method('save');

        $useCase = new DeleteGroupUseCase(
            $groupRepository,
            $this->createStub(UserRepositoryInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(EntityDeletedException::class);

        $useCase->execute(new DeleteGroupInputDto(groupId: self::GROUP_ID));
    }

    #[Test]
    public function throwsGroupHasActiveUsersExceptionWhenUsersExist(): void
    {
        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->buildActiveGroup());

        $userRepository
            ->expects($this->once())
            ->method('countActiveUsersByGroupId')
            ->willReturn(3);

        $groupRepository
            ->expects($this->never())
            ->method('save');

        $useCase = new DeleteGroupUseCase(
            $groupRepository,
            $userRepository,
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(GroupHasActiveUsersException::class);

        $useCase->execute(new DeleteGroupInputDto(groupId: self::GROUP_ID));
    }

    #[Test]
    public function dispatchesEventsAfterSave(): void
    {
        $group = $this->buildActiveGroup();

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository->method('findById')->willReturn($group);

        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('countActiveUsersByGroupId')->willReturn(0);

        $saveCallOrder = 0;
        $dispatchCallOrder = 0;

        $groupRepository
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
                $this->assertGreaterThan(0, $saveCallOrder, 'dispatch() вызван до save()');
                $dispatchCallOrder++;

                return $event;
            });

        $useCase = new DeleteGroupUseCase($groupRepository, $userRepository, $eventDispatcher);
        $useCase->execute(new DeleteGroupInputDto(groupId: self::GROUP_ID));

        $this->assertGreaterThan(0, $dispatchCallOrder, 'dispatch() не был вызван ни разу');
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
