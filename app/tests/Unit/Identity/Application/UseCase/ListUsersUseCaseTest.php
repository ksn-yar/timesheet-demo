<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\UseCase;

use App\Identity\Application\Dto\ListUsersInputDto;
use App\Identity\Application\Dto\ListUsersOutputDto;
use App\Identity\Application\Dto\UserItemDto;
use App\Identity\Application\Port\ListUsersOutputPortInterface;
use App\Identity\Application\UseCase\ListUsersUseCase;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\SystemRole;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\Email;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Тесты Use Case получения списка пользователей с пагинацией и фильтрацией.
 *
 * @internal
 *
 * @coversNothing
 */
final class ListUsersUseCaseTest extends TestCase
{
    private const string USER_ID = '550e8400-e29b-41d4-a716-446655440001';
    private const string USER_ID_2 = '550e8400-e29b-41d4-a716-446655440004';
    private const string GROUP_ID = '550e8400-e29b-41d4-a716-446655440002';

    #[Test]
    public function callsPresenterWithMappedUsers(): void
    {
        $user1 = $this->buildActiveUser(self::USER_ID, 'Иван Иванов', 'ivan@example.com');
        $user2 = $this->buildActiveUser(self::USER_ID_2, 'Пётр Петров', 'petr@example.com');

        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findAll')->willReturn([
            'items' => [$user1, $user2],
            'total' => 2,
        ]);

        $presenter = $this->createMock(ListUsersOutputPortInterface::class);
        $presenter
            ->expects($this->once())
            ->method('present')
            ->with($this->callback(function (ListUsersOutputDto $dto): bool {
                $this->assertCount(2, $dto->items);
                $this->assertSame(2, $dto->total);
                $this->assertSame(1, $dto->page);
                $this->assertSame(20, $dto->perPage);
                $this->assertInstanceOf(UserItemDto::class, $dto->items[0]);
                $this->assertSame(self::USER_ID, $dto->items[0]->id);
                $this->assertSame('Иван Иванов', $dto->items[0]->name);
                $this->assertSame('ivan@example.com', $dto->items[0]->email);
                $this->assertSame(self::USER_ID_2, $dto->items[1]->id);

                return true;
            }))
        ;

        $useCase = new ListUsersUseCase($userRepository, $presenter);
        $useCase->execute(new ListUsersInputDto(page: 1, perPage: 20));
    }

    #[Test]
    public function passesGroupIdFilterToCriteria(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->equalTo(['groupId' => self::GROUP_ID]), 1, 20)
            ->willReturn(['items' => [], 'total' => 0])
        ;

        $useCase = new ListUsersUseCase(
            $userRepository,
            $this->createStub(ListUsersOutputPortInterface::class),
        );

        $useCase->execute(new ListUsersInputDto(groupId: self::GROUP_ID, page: 1, perPage: 20));
    }

    #[Test]
    public function passesIsActiveFilterToCriteria(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->equalTo(['isActive' => true]), 1, 20)
            ->willReturn(['items' => [], 'total' => 0])
        ;

        $useCase = new ListUsersUseCase(
            $userRepository,
            $this->createStub(ListUsersOutputPortInterface::class),
        );

        $useCase->execute(new ListUsersInputDto(isActive: true, page: 1, perPage: 20));
    }

    #[Test]
    public function omitsNullFiltersFromCriteria(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->equalTo([]), 1, 20)
            ->willReturn(['items' => [], 'total' => 0])
        ;

        $useCase = new ListUsersUseCase(
            $userRepository,
            $this->createStub(ListUsersOutputPortInterface::class),
        );

        $useCase->execute(new ListUsersInputDto(
            groupId: null,
            roleId: null,
            isActive: null,
            page: 1,
            perPage: 20,
        ));
    }

    #[Test]
    public function callsPresenterWithEmptyList(): void
    {
        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findAll')->willReturn([
            'items' => [],
            'total' => 0,
        ]);

        $presenter = $this->createMock(ListUsersOutputPortInterface::class);
        $presenter
            ->expects($this->once())
            ->method('present')
            ->with($this->callback(function (ListUsersOutputDto $dto): bool {
                $this->assertCount(0, $dto->items);
                $this->assertSame(0, $dto->total);

                return true;
            }))
        ;

        $useCase = new ListUsersUseCase($userRepository, $presenter);
        $useCase->execute(new ListUsersInputDto(page: 1, perPage: 20));
    }

    private function buildActiveUser(string $id, string $name, string $email): User
    {
        return User::restore(
            new UserId($id),
            $name,
            new Email($email),
            new HashedPassword('$2y$hash'),
            SystemRole::Employee,
            null,
            null,
            true,
            null,
        );
    }
}
