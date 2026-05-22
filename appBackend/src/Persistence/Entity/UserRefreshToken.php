<?php

declare(strict_types=1);

namespace App\Persistence\Entity;

use App\Persistence\Repository\UserRefreshTokenRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Model\AbstractRefreshToken;

/**
 * Refresh token пользователя для JWT-аутентификации.
 * Хранит связь с конкретным пользователем, чтобы можно было
 * инвалидировать все токены пользователя при необходимости.
 */
#[ORM\Entity(repositoryClass: UserRefreshTokenRepository::class)]
#[ORM\Table(name: 'user_refresh_tokens')]
#[ORM\Index(name: 'idx_user_refresh_tokens_username', columns: ['username'])]
#[ORM\Index(name: 'idx_user_refresh_tokens_valid', columns: ['valid'])]
#[ORM\Index(name: 'idx_user_refresh_tokens_user_id', columns: ['user_id'])]
class UserRefreshToken extends AbstractRefreshToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected int|string|null $id = null;

    #[ORM\Column(name: 'refresh_token', type: 'string', length: 128, unique: true)]
    protected ?string $refreshToken = null;

    #[ORM\Column(name: 'username', type: 'string', length: 255)]
    protected ?string $username = null;

    #[ORM\Column(name: 'valid', type: 'datetime')]
    protected ?DateTimeInterface $valid = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
