<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/** Проверяет соответствие открытого пароля сохранённому хэшу. */
interface PasswordVerifierInterface
{
    public function isValid(PasswordAuthenticatedUserInterface $user, string $plainPassword): bool;
}
