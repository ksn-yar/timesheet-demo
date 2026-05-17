<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Port;

use App\Persistence\Entity\User;
use App\Timesheet\Application\Port\CurrentUserProviderInterface;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/** Предоставляет данные текущего пользователя из Symfony Security. */
final readonly class SecurityCurrentUserProvider implements CurrentUserProviderInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function getCurrentUserId(): string
    {
        return (string) $this->getUser()->getId();
    }

    public function getCurrentUserRole(): string
    {
        return $this->getUser()->getSystemRole();
    }

    public function isAdmin(): bool
    {
        return 'admin' === $this->getCurrentUserRole();
    }

    public function isManager(): bool
    {
        return 'manager' === $this->getCurrentUserRole();
    }

    public function isEmployee(): bool
    {
        return 'employee' === $this->getCurrentUserRole();
    }

    private function getUser(): User
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if (!$user instanceof User) {
            throw new LogicException('Пользователь не аутентифицирован.');
        }

        return $user;
    }
}
