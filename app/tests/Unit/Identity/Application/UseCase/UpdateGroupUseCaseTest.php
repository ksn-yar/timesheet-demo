<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\UseCase;

use App\Identity\Application\Dto\UpdateGroupInputDto;
use App\Identity\Application\UseCase\UpdateGroupUseCase;
use App\Identity\Domain\Entity\Group;
use App\Identity\Domain\Exception\DuplicateGroupNameException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Domain\Repository\GroupRepositoryInterface;
use App\Identity\Domain\ValueObject\GroupId;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Тесты Use Case обновления атрибутов группы. */
final class UpdateGroupUseCaseTest extends TestCase
{
    private const string GROUP_ID = '550e8400-e29b-41d4-a716-446655440002';

    #[Test]
    public function updatesGroupAndDispatchesEvents(): void
    {
        $group = $this->buildActiveGroup('Старое имя');

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($group);

        $groupRepository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Новое имя')
            ->willReturn(false);

        $groupRepository
            ->expects($this->once())
            ->method('save')
            ->with($group);

        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch');

        $useCase = new UpdateGroupUseCase($groupRepository, $eventDispatcher);
        $useCase->execute(new UpdateGroupInputDto(groupId: self::GROUP_ID, name: 'Новое имя', description: null));
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

        $useCase = new UpdateGroupUseCase(
            $groupRepository,
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(GroupNotFoundException::class);

        $useCase->execute(new UpdateGroupInputDto(groupId: self::GROUP_ID, name: 'Новое имя', description: null));
    }

    #[Test]
    public function throwsDuplicateGroupNameExceptionWhenNewNameConflicts(): void
    {
        $group = $this->buildActiveGroup('Старое имя');

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);

        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($group);

        $groupRepository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Новое имя')
            ->willReturn(true);

        $groupRepository
            ->expects($this->never())
            ->method('save');

        $useCase = new UpdateGroupUseCase(
            $groupRepository,
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(DuplicateGroupNameException::class);

        $useCase->execute(new UpdateGroupInputDto(groupId: self::GROUP_ID, name: 'Новое имя', description: null));
    }

    #[Test]
    public function doesNotCheckDuplicateWhenNameUnchanged(): void
    {
        $group = $this->buildActiveGroup('Разработка');

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);

        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($group);

        $groupRepository
            ->expects($this->never())
            ->method('existsByName');

        $groupRepository
            ->expects($this->once())
            ->method('save');

        $useCase = new UpdateGroupUseCase(
            $groupRepository,
            $this->createStub(EventDispatcherInterface::class),
        );

        // имя не изменилось — existsByName не должен вызываться
        $useCase->execute(new UpdateGroupInputDto(groupId: self::GROUP_ID, name: 'Разработка', description: 'Новое описание'));
    }

    #[Test]
    public function dispatchesEventsAfterSave(): void
    {
        $group = $this->buildActiveGroup('Старое имя');

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository->method('findById')->willReturn($group);
        $groupRepository->method('existsByName')->willReturn(false);

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

        $useCase = new UpdateGroupUseCase($groupRepository, $eventDispatcher);
        $useCase->execute(new UpdateGroupInputDto(groupId: self::GROUP_ID, name: 'Новое имя', description: null));

        $this->assertGreaterThan(0, $dispatchCallOrder, 'dispatch() не был вызван ни разу');
    }

    private function buildActiveGroup(string $name): Group
    {
        return Group::restore(
            new GroupId(self::GROUP_ID),
            $name,
            null,
            null,
        );
    }

    private function buildDeletedGroup(): Group
    {
        return Group::restore(
            new GroupId(self::GROUP_ID),
            'Разработка',
            null,
            new DateTimeImmutable(),
        );
    }
}
