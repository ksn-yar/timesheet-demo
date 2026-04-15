<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Port\PasswordHasherInterface;
use App\Identity\Domain\ValueObject\HashedPassword;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/** Реализация хэширования паролей через Symfony PasswordHasher. */
final class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private readonly PasswordHasherFactoryInterface $hasherFactory,
    ) {}

    public function hash(string $plainPassword): HashedPassword
    {
        $hasher = $this->hasherFactory->getPasswordHasher('default');

        return new HashedPassword($hasher->hash($plainPassword));
    }
}
