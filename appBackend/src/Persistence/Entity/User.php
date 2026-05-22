<?php

declare(strict_types=1);

namespace App\Persistence\Entity;

use App\Persistence\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Doctrine-сущность пользователя. Реализует UserInterface для интеграции
 * с Symfony Security — используется как субъект аутентификации.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uq_users_email', columns: ['email'])]
#[ORM\Index(columns: ['deleted_at'], name: 'idx_users_deleted_at')]
#[ORM\Index(columns: ['group_id'], name: 'idx_users_group_id')]
#[ORM\Index(columns: ['role_id'], name: 'idx_users_role_id')]
#[ORM\Index(columns: ['is_active', 'deleted_at'], name: 'idx_users_active_deleted')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\Column(name: 'system_role', type: 'string', length: 50)]
    private string $systemRole;

    /** Связь с группой пользователей — владелец ассоциации (хранит FK в своей таблице). */
    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: true)]
    private ?Group $group = null;

    /** Связь с ролью из WorkCatalog — владелец ассоциации (хранит FK в своей таблице). */
    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: true)]
    private ?Role $role = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getSystemRole(): string
    {
        return $this->systemRole;
    }

    public function setSystemRole(string $systemRole): void
    {
        $this->systemRole = $systemRole;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): void
    {
        $this->group = $group;
    }

    /** Возвращает UUID группы для обратной совместимости с существующим кодом. */
    public function getGroupId(): ?string
    {
        return $this->group?->getId();
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): void
    {
        $this->role = $role;
    }

    /** Возвращает UUID роли для обратной совместимости с существующим кодом. */
    public function getRoleId(): ?string
    {
        return $this->role?->getId();
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    // --- Реализация UserInterface ---

    /** Возвращает уникальный идентификатор пользователя для Symfony Security. */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * Возвращает роли Symfony Security на основе системной роли пользователя.
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        return ['ROLE_' . mb_strtoupper($this->systemRole), 'ROLE_USER'];
    }

    /** Возвращает хэш пароля для Symfony PasswordHasher. */
    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    /** Очищает чувствительные данные после аутентификации (не требуется при хэшировании bcrypt/argon). */
    public function eraseCredentials(): void {}
}
