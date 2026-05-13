<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Port\PasswordHasherInterface;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Persistence\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Реализация хэширования паролей через Symfony UserPasswordHasher. */
final class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function hash(string $plainPassword): HashedPassword
    {
        // Используем фиктивного пользователя только для выбора алгоритма;
        // сам хэш не зависит от конкретного экземпляра сущности
        $hash = $this->passwordHasher->hashPassword(new User(), $plainPassword);

        return new HashedPassword($hash);
    }
}
