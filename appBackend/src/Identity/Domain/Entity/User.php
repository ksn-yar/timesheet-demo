<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entity;

use App\Identity\Domain\Enum\SystemRole;
use App\Identity\Domain\Event\UserAssignedToGroup;
use App\Identity\Domain\Event\UserCreated;
use App\Identity\Domain\Event\UserDeactivated;
use App\Identity\Domain\Event\UserDeleted;
use App\Identity\Domain\Event\UserRemovedFromGroup;
use App\Identity\Domain\Event\UserUpdated;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\UserAlreadyDeactivatedException;
use App\Identity\Domain\Trait\RecordsDomainEventsTrait;
use App\Identity\Domain\ValueObject\Email;
use App\Identity\Domain\ValueObject\GroupId;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Сущность пользователя системы. Управляет жизненным циклом учётной записи. */
final class User
{
    use RecordsDomainEventsTrait;

    private UserId $id;
    private string $name;
    private Email $email;
    private HashedPassword $passwordHash;
    private SystemRole $systemRole;
    private ?GroupId $groupId;
    private ?string $roleId;
    private bool $isActive;
    private ?DateTimeImmutable $deletedAt;

    private function __construct() {}

    public static function create(
        UserId $id,
        string $name,
        Email $email,
        HashedPassword $passwordHash,
        SystemRole $systemRole,
        ?GroupId $groupId,
        ?string $roleId,
    ): self {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('Имя пользователя не может быть пустым.');
        }

        $user = new self();
        $user->id = $id;
        $user->name = $name;
        $user->email = $email;
        $user->passwordHash = $passwordHash;
        $user->systemRole = $systemRole;
        $user->groupId = $groupId;
        $user->roleId = $roleId;
        $user->isActive = true;
        $user->deletedAt = null;

        $user->recordEvent(new UserCreated(
            $user->id,
            $user->name,
            $user->email->value(),
            $user->systemRole,
        ));

        return $user;
    }

    public static function restore(
        UserId $id,
        string $name,
        Email $email,
        HashedPassword $passwordHash,
        SystemRole $systemRole,
        ?GroupId $groupId,
        ?string $roleId,
        bool $isActive,
        ?DateTimeImmutable $deletedAt,
    ): self {
        $user = new self();
        $user->id = $id;
        $user->name = $name;
        $user->email = $email;
        $user->passwordHash = $passwordHash;
        $user->systemRole = $systemRole;
        $user->groupId = $groupId;
        $user->roleId = $roleId;
        $user->isActive = $isActive;
        $user->deletedAt = $deletedAt;

        return $user;
    }

    public function deactivate(): void
    {
        if ($this->isDeleted()) {
            throw new EntityDeletedException();
        }

        if (!$this->isActive) {
            throw new UserAlreadyDeactivatedException($this->id->value());
        }

        $this->isActive = false;

        $this->recordEvent(new UserDeactivated($this->id));
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $this->recordEvent(new UserDeleted($this->id));
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function assignToGroup(GroupId $groupId): void
    {
        if ($this->isDeleted()) {
            throw new EntityDeletedException();
        }

        $this->groupId = $groupId;

        $this->recordEvent(new UserAssignedToGroup($this->id, $groupId));
    }

    public function removeFromGroup(): void
    {
        if ($this->isDeleted()) {
            throw new EntityDeletedException();
        }

        $this->groupId = null;

        $this->recordEvent(new UserRemovedFromGroup($this->id));
    }

    public function update(string $name, SystemRole $systemRole, ?string $roleId): void
    {
        if ($this->isDeleted()) {
            throw new EntityDeletedException();
        }

        if ('' === trim($name)) {
            throw new InvalidArgumentException('Имя пользователя не может быть пустым.');
        }

        $this->name = $name;
        $this->systemRole = $systemRole;
        $this->roleId = $roleId;

        $this->recordEvent(new UserUpdated($this->id));
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): HashedPassword
    {
        return $this->passwordHash;
    }

    public function getSystemRole(): SystemRole
    {
        return $this->systemRole;
    }

    public function getGroupId(): ?GroupId
    {
        return $this->groupId;
    }

    public function getRoleId(): ?string
    {
        return $this->roleId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
