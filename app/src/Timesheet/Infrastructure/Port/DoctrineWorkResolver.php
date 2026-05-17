<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Port;

use App\Persistence\Entity\Work as WorkOrmEntity;
use App\Persistence\Repository\WorkRepository;
use App\Timesheet\Application\Port\WorkResolverInterface;
use InvalidArgumentException;

/** Разрешает идентификатор вида работ по имени через Doctrine ORM. */
final readonly class DoctrineWorkResolver implements WorkResolverInterface
{
    public function __construct(
        private WorkRepository $workRepository,
    ) {}

    public function resolve(string $value, string $matchBy): ?string
    {
        $qb = $this->workRepository->createQueryBuilder('w')
            ->where('w.deletedAt IS NULL')
        ;

        match ($matchBy) {
            'name' => $qb->andWhere('w.name = :value'),
            default => throw new InvalidArgumentException("Неизвестный matchBy: {$matchBy}"),
        };

        /** @var null|WorkOrmEntity $work */
        $work = $qb->setParameter('value', $value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $work?->getId();
    }
}
