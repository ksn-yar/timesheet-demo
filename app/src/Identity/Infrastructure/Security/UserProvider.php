<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Persistence\Entity\User;
use App\Persistence\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Провайдер пользователей Symfony Security. Загружает пользователя из БД по email
 * и поддерживает обновление токена безопасности между запросами.
 *
 * @implements UserProviderInterface<User>
 */
final class UserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findByEmail($identifier);

        if (null === $user) {
            throw new UserNotFoundException(sprintf('Пользователь с email "%s" не найден.', $identifier));
        }

        if (!$user->isActive()) {
            throw new UserNotFoundException(sprintf('Пользователь "%s" деактивирован.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Тип "%s" не поддерживается.', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
