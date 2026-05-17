<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\EventListener;

use App\WorkCatalog\Domain\Event\RateCreated;
use App\WorkCatalog\Domain\Event\RateDeleted;
use App\WorkCatalog\Domain\Event\RateUpdated;
use App\WorkCatalog\Domain\Event\RoleCreated;
use App\WorkCatalog\Domain\Event\RoleDeleted;
use App\WorkCatalog\Domain\Event\WorkCreated;
use App\WorkCatalog\Domain\Event\WorkDeleted;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/** Слушатель доменных событий Work Catalog для ведения аудит-лога. */
final readonly class AuditLogEventListener
{
    public function __construct(
        private LoggerInterface $logger,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function onWorkCreated(WorkCreated $event): void
    {
        $this->log('create', 'Work', $event->workId->value());
    }

    public function onWorkDeleted(WorkDeleted $event): void
    {
        $this->log('delete', 'Work', $event->workId->value());
    }

    public function onRoleCreated(RoleCreated $event): void
    {
        $this->log('create', 'Role', $event->roleId->value());
    }

    public function onRoleDeleted(RoleDeleted $event): void
    {
        $this->log('delete', 'Role', $event->roleId->value());
    }

    public function onRateCreated(RateCreated $event): void
    {
        $this->log('create', 'Rate', $event->rateId->value());
    }

    public function onRateUpdated(RateUpdated $event): void
    {
        $this->log('update', 'Rate', $event->rateId->value());
    }

    public function onRateDeleted(RateDeleted $event): void
    {
        $this->log('delete', 'Rate', $event->rateId->value());
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
