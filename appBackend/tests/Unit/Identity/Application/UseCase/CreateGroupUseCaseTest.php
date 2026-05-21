<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\UseCase;

use App\Identity\Application\Dto\CreateGroupInputDto;
use App\Identity\Application\UseCase\CreateGroupUseCase;
use App\Identity\Domain\Entity\Group;
use App\Identity\Domain\Exception\DuplicateGroupNameException;
use App\Identity\Domain\Repository\GroupRepositoryInterface;
use App\Identity\Domain\ValueObject\GroupId;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Тесты Use Case создания группы.
 *
 * @internal
 *
 * @coversNothing
 */
final class CreateGroupUseCaseTest extends TestCase
{
    private const string GROUP_ID = '550e8400-e29b-41d4-a716-446655440002';

    #[Test]
    public function createsGroupAndDispatchesEventsWhenNameIsUnique(): void
    {
        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $groupRepository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Разработка')
            ->willReturn(false)
        ;

        $groupRepository
            ->expects($this->once())
            ->method('save')
        ;

        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
        ;

        $useCase = new CreateGroupUseCase($groupRepository, $eventDispatcher);
        $useCase->execute(new CreateGroupInputDto(name: 'Разработка', description: null));
    }

    #[Test]
    public function throwsDuplicateGroupNameExceptionWhenNameExists(): void
    {
        $groupRepository = $this->createMock(GroupRepositoryInterface::class);

        $groupRepository
            ->expects($this->once())
            ->method('existsByName')
            ->willReturn(true)
        ;

        $groupRepository
            ->expects($this->never())
            ->method('save')
        ;

        $useCase = new CreateGroupUseCase(
            $groupRepository,
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(DuplicateGroupNameException::class);

        $useCase->execute(new CreateGroupInputDto(name: 'Разработка', description: null));
    }

    #[Test]
    public function dispatchesEventsAfterSave(): void
    {
        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository->method('existsByName')->willReturn(false);

        $saveCallOrder = 0;
        $dispatchCallOrder = 0;

        $groupRepository
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

        $useCase = new CreateGroupUseCase($groupRepository, $eventDispatcher);
        $useCase->execute(new CreateGroupInputDto(name: 'Разработка', description: null));

        $this->assertGreaterThan(0, $dispatchCallOrder, 'dispatch() не был вызван ни разу');
    }

    private function buildActiveGroup(): Group
    {
        return Group::restore(
            new GroupId(self::GROUP_ID),
            'Разработка',
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
