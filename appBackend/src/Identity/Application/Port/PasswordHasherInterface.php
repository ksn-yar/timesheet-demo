<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Domain\ValueObject\HashedPassword;

/** Контракт хэширования паролей. Инфраструктурная реализация определяет алгоритм. */
interface PasswordHasherInterface
{
    public function hash(string $plainPassword): HashedPassword;
}
