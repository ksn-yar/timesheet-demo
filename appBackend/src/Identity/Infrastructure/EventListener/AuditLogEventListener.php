<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\EventListener;

use App\Identity\Domain\Event\GroupCreated;
use App\Identity\Domain\Event\GroupDeleted;
use App\Identity\Domain\Event\GroupUpdated;
use App\Identity\Domain\Event\UserAssignedToGroup;
use App\Identity\Domain\Event\UserCreated;
use App\Identity\Domain\Event\UserDeactivated;
use App\Identity\Domain\Event\UserDeleted;
use App\Identity\Domain\Event\UserRemovedFromGroup;
use App\Identity\Domain\Event\UserUpdated;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/** Слушатель доменных событий Identity для ведения аудит-лога. */
final readonly class AuditLogEventListener
{
    public function __construct(
        private LoggerInterface $logger,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function onUserCreated(UserCreated $event): void
    {
        $this->log('create', 'User', $event->userId->value());
    }

    public function onUserUpdated(UserUpdated $event): void
    {
        $this->log('update', 'User', $event->userId->value());
    }

    public function onUserDeactivated(UserDeactivated $event): void
    {
        $this->log('deactivate', 'User', $event->userId->value());
    }

    public function onUserDeleted(UserDeleted $event): void
    {
        $this->log('delete', 'User', $event->userId->value());
    }

    public function onUserAssignedToGroup(UserAssignedToGroup $event): void
    {
        $this->log('assign_to_group', 'User', $event->userId->value());
    }

    public function onUserRemovedFromGroup(UserRemovedFromGroup $event): void
    {
        $this->log('remove_from_group', 'User', $event->userId->value());
    }

    public function onGroupCreated(GroupCreated $event): void
    {
        $this->log('create', 'Group', $event->groupId->value());
    }

    public function onGroupUpdated(GroupUpdated $event): void
    {
        $this->log('update', 'Group', $event->groupId->value());
    }

    public function onGroupDeleted(GroupDeleted $event): void
    {
        $this->log('delete', 'Group', $event->groupId->value());
    }

    private function log(string $action, string $entityType, string $entityId): void
    {
        $userId = null;
        $token = $this->tokenStorage->getToken();

        if (null !== $token && null !== $token->getUser()) {
            $user = $token->getUser();
            $userId = method_exists($user, 'getId')
                ? (string) $user->getId()
                : $user->getUserIdentifier();
        }

        $this->logger->info('audit_log', [
            'userId' => $userId,
            'action' => $action,
            'entityType' => $entityType,
            'entityId' => $entityId,
            'timestamp' => new DateTimeImmutable()->format(DateTimeInterface::RFC3339),
        ]);
    }
}
