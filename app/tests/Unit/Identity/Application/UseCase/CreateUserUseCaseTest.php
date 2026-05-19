<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\UseCase;

use App\Identity\Application\Dto\CreateUserInputDto;
use App\Identity\Application\Port\PasswordHasherInterface;
use App\Identity\Application\UseCase\CreateUserUseCase;
use App\Identity\Domain\Entity\Group;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\SystemRole;
use App\Identity\Domain\Exception\DuplicateEmailException;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupNotFoundException;
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

/** Тесты Use Case создания пользователя. */
final class CreateUserUseCaseTest extends TestCase
{
    private const string USER_ID = '550e8400-e29b-41d4-a716-446655440001';
    private const string GROUP_ID = '550e8400-e29b-41d4-a716-446655440002';

    #[Test]
    public function createsUserWithoutGroupAndDispatchesEvents(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('existsByEmail')
            ->willReturn(false);

        $userRepository
            ->expects($this->once())
            ->method('save');

        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch');

        $passwordHasher = $this->createStub(PasswordHasherInterface::class);
        $passwordHasher->method('hash')->willReturn(new HashedPassword('$2y$hash'));

        $useCase = new CreateUserUseCase(
            $userRepository,
            $this->createStub(GroupRepositoryInterface::class),
            $passwordHasher,
            $eventDispatcher,
        );

        $useCase->execute(new CreateUserInputDto(
            name: 'Иван',
            email: 'ivan@example.com',
            password: 'secret',
            systemRole: 'employee',
            groupId: null,
            roleId: null,
        ));
    }

    #[Test]
    public function createsUserWithGroupWhenGroupExists(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $groupRepository = $this->createMock(GroupRepositoryInterface::class);

        $userRepository->method('existsByEmail')->willReturn(false);
        $userRepository
            ->expects($this->once())
            ->method('save');

        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->buildActiveGroup());

        $passwordHasher = $this->createStub(PasswordHasherInterface::class);
        $passwordHasher->method('hash')->willReturn(new HashedPassword('$2y$hash'));

        $useCase = new CreateUserUseCase(
            $userRepository,
            $groupRepository,
            $passwordHasher,
            $this->createStub(EventDispatcherInterface::class),
        );

        $useCase->execute(new CreateUserInputDto(
            name: 'Иван',
            email: 'ivan@example.com',
            password: 'secret',
            systemRole: 'employee',
            groupId: self::GROUP_ID,
            roleId: null,
        ));
    }

    #[Test]
    public function throwsDuplicateEmailExceptionWhenEmailExists(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('existsByEmail')
            ->willReturn(true);

        $userRepository
            ->expects($this->never())
            ->method('save');

        $useCase = new CreateUserUseCase(
            $userRepository,
            $this->createStub(GroupRepositoryInterface::class),
            $this->createStub(PasswordHasherInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(DuplicateEmailException::class);

        $useCase->execute(new CreateUserInputDto(
            name: 'Иван',
            email: 'ivan@example.com',
            password: 'secret',
            systemRole: 'employee',
            groupId: null,
            roleId: null,
        ));
    }

    #[Test]
    public function throwsGroupNotFoundExceptionWhenGroupNotFound(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $groupRepository = $this->createMock(GroupRepositoryInterface::class);

        $userRepository->method('existsByEmail')->willReturn(false);
        $userRepository
            ->expects($this->never())
            ->method('save');

        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $useCase = new CreateUserUseCase(
            $userRepository,
            $groupRepository,
            $this->createStub(PasswordHasherInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(GroupNotFoundException::class);

        $useCase->execute(new CreateUserInputDto(
            name: 'Иван',
            email: 'ivan@example.com',
            password: 'secret',
            systemRole: 'employee',
            groupId: self::GROUP_ID,
            roleId: null,
        ));
    }

    #[Test]
    public function throwsEntityDeletedExceptionWhenGroupIsDeleted(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $groupRepository = $this->createMock(GroupRepositoryInterface::class);

        $userRepository->method('existsByEmail')->willReturn(false);
        $userRepository
            ->expects($this->never())
            ->method('save');

        $groupRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->buildDeletedGroup());

        $useCase = new CreateUserUseCase(
            $userRepository,
            $groupRepository,
            $this->createStub(PasswordHasherInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $this->expectException(EntityDeletedException::class);

        $useCase->execute(new CreateUserInputDto(
            name: 'Иван',
            email: 'ivan@example.com',
            password: 'secret',
            systemRole: 'employee',
            groupId: self::GROUP_ID,
            roleId: null,
        ));
    }

    #[Test]
    public function hashesPasswordBeforeSave(): void
    {
        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $userRepository->method('existsByEmail')->willReturn(false);

        $passwordHasher
            ->expects($this->once())
            ->method('hash')
            ->with('secret')
            ->willReturn(new HashedPassword('$2y$hash'));

        $useCase = new CreateUserUseCase(
            $userRepository,
            $this->createStub(GroupRepositoryInterface::class),
            $passwordHasher,
            $this->createStub(EventDispatcherInterface::class),
        );

        $useCase->execute(new CreateUserInputDto(
            name: 'Иван',
            email: 'ivan@example.com',
            password: 'secret',
            systemRole: 'employee',
            groupId: null,
            roleId: null,
        ));
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
