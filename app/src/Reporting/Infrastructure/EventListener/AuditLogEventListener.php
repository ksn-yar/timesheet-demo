<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\EventListener;

use App\Reporting\Domain\Event\ReportAdded;
use App\Reporting\Domain\Event\ReportsExported;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/** Слушатель доменных событий Reporting для ведения аудит-лога. */
final class AuditLogEventListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function onReportAdded(ReportAdded $event): void
    {
        $this->log('create_report', 'Report', $event->reportId->value());
    }

    public function onReportsExported(ReportsExported $event): void
    {
        $this->log('export_reports', 'ReportExport', implode(',', $event->reportIds));
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
