<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Port;

use App\Persistence\Entity\Rate as RateOrmEntity;
use App\Persistence\Entity\User as UserOrmEntity;
use App\Persistence\Repository\RateRepository;
use App\Persistence\Repository\UserRepository;
use App\Timesheet\Application\Port\RateProviderInterface;
use DateTimeImmutable;

/**
 * Определяет текущую ставку через приоритетную цепочку.
 * Приоритет: Work+Role > Work > Role > базовая (0).
 */
final readonly class DoctrineRateProvider implements RateProviderInterface
{
    public function __construct(
        private RateRepository $rateRepository,
        private UserRepository $userRepository,
    ) {}

    public function getCurrentRate(string $employeeId, string $workId): string
    {
        /** @var null|UserOrmEntity $user */
        $user = $this->userRepository->find($employeeId);
        $roleId = $user?->getRoleId();

        $today = new DateTimeImmutable('today');

        // Приоритет 1: work + role
        if (null !== $roleId) {
            $rate = $this->findRate($workId, $roleId, $today);
            if (null !== $rate) {
                return $rate;
            }
        }

        // Приоритет 2: только work
        $rate = $this->findRate($workId, null, $today);
        if (null !== $rate) {
            return $rate;
        }

        // Приоритет 3: только role
        if (null !== $roleId) {
            $rate = $this->findRate(null, $roleId, $today);
            if (null !== $rate) {
                return $rate;
            }
        }

        return '0';
    }

    private function findRate(?string $workId, ?string $roleId, DateTimeImmutable $date): ?string
    {
        $qb = $this->rateRepository->createQueryBuilder('r')
            ->where('r.deletedAt IS NULL')
            ->andWhere('r.effectiveFrom <= :date')
            ->setParameter('date', $date)
            ->orderBy('r.effectiveFrom', 'DESC')
            ->setMaxResults(1)
        ;

        if (null !== $workId) {
            $qb->andWhere('r.workId = :workId')->setParameter('workId', $workId);
        } else {
            $qb->andWhere('r.workId IS NULL');
        }

        if (null !== $roleId) {
            $qb->andWhere('r.roleId = :roleId')->setParameter('roleId', $roleId);
        } else {
            $qb->andWhere('r.roleId IS NULL');
        }

        /** @var null|RateOrmEntity $rate */
        $rate = $qb->getQuery()->getOneOrNullResult();

        return $rate?->getAmount();
    }
}
