<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Port;

use App\Persistence\Entity\Task as TaskOrmEntity;
use App\Persistence\Repository\TaskRepository;
use App\Timesheet\Application\Port\TaskResolverInterface;
use InvalidArgumentException;

/** Разрешает идентификатор задачи по имени через Doctrine ORM. */
final readonly class DoctrineTaskResolver implements TaskResolverInterface
{
    public function __construct(
        private TaskRepository $taskRepository,
    ) {}

    public function resolve(string $value, string $matchBy): ?string
    {
        $qb = $this->taskRepository->createQueryBuilder('t')
            ->where('t.deletedAt IS NULL')
        ;

        match ($matchBy) {
            'name' => $qb->andWhere('t.name = :value'),
            default => throw new InvalidArgumentException("Неизвестный matchBy: {$matchBy}"),
        };

        /** @var null|TaskOrmEntity $task */
        $task = $qb->setParameter('value', $value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $task?->getId();
    }
}
