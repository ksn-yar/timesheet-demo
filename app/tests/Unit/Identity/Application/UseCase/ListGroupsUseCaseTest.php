<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\UseCase;

use App\Identity\Application\Dto\GroupItemDto;
use App\Identity\Application\Dto\ListGroupsInputDto;
use App\Identity\Application\Dto\ListGroupsOutputDto;
use App\Identity\Application\Port\ListGroupsOutputPortInterface;
use App\Identity\Application\UseCase\ListGroupsUseCase;
use App\Identity\Domain\Entity\Group;
use App\Identity\Domain\Repository\GroupRepositoryInterface;
use App\Identity\Domain\ValueObject\GroupId;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Тесты Use Case получения списка групп с пагинацией. */
final class ListGroupsUseCaseTest extends TestCase
{
    private const string GROUP_ID = '550e8400-e29b-41d4-a716-446655440002';
    private const string GROUP_ID_2 = '550e8400-e29b-41d4-a716-446655440003';

    #[Test]
    public function callsPresenterWithMappedItems(): void
    {
        $group1 = $this->buildActiveGroup(self::GROUP_ID, 'Разработка');
        $group2 = $this->buildActiveGroup(self::GROUP_ID_2, 'Тестирование');

        $groupRepository = $this->createStub(GroupRepositoryInterface::class);
        $groupRepository->method('findAll')->willReturn([
            'items' => [$group1, $group2],
            'total' => 2,
        ]);

        $presenter = $this->createMock(ListGroupsOutputPortInterface::class);
        $presenter
            ->expects($this->once())
            ->method('present')
            ->with($this->callback(function (ListGroupsOutputDto $dto): bool {
                $this->assertCount(2, $dto->items);
                $this->assertSame(2, $dto->total);
                $this->assertSame(1, $dto->page);
                $this->assertSame(20, $dto->perPage);
                $this->assertInstanceOf(GroupItemDto::class, $dto->items[0]);
                $this->assertSame(self::GROUP_ID, $dto->items[0]->id);
                $this->assertSame('Разработка', $dto->items[0]->name);
                $this->assertSame(self::GROUP_ID_2, $dto->items[1]->id);

                return true;
            }));

        $useCase = new ListGroupsUseCase($groupRepository, $presenter);
        $useCase->execute(new ListGroupsInputDto(page: 1, perPage: 20));
    }

    #[Test]
    public function callsPresenterWithEmptyList(): void
    {
        $groupRepository = $this->createStub(GroupRepositoryInterface::class);
        $groupRepository->method('findAll')->willReturn([
            'items' => [],
            'total' => 0,
        ]);

        $presenter = $this->createMock(ListGroupsOutputPortInterface::class);
        $presenter
            ->expects($this->once())
            ->method('present')
            ->with($this->callback(function (ListGroupsOutputDto $dto): bool {
                $this->assertCount(0, $dto->items);
                $this->assertSame(0, $dto->total);

                return true;
            }));

        $useCase = new ListGroupsUseCase($groupRepository, $presenter);
        $useCase->execute(new ListGroupsInputDto(page: 1, perPage: 20));
    }

    #[Test]
    public function passesPageAndPerPageToRepository(): void
    {
        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->equalTo([]), 2, 5)
            ->willReturn(['items' => [], 'total' => 0]);

        $useCase = new ListGroupsUseCase(
            $groupRepository,
            $this->createStub(ListGroupsOutputPortInterface::class),
        );

        $useCase->execute(new ListGroupsInputDto(page: 2, perPage: 5));
    }

    private function buildActiveGroup(string $id, string $name): Group
    {
        return Group::restore(
            new GroupId($id),
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
