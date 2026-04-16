<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Port;

use App\Persistence\Entity\User as UserOrmEntity;
use App\Persistence\Repository\UserRepository;
use App\Timesheet\Application\Port\EmployeeResolverInterface;
use InvalidArgumentException;

/** Разрешает идентификатор сотрудника по email через Doctrine ORM. */
final class DoctrineEmployeeResolver implements EmployeeResolverInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function resolve(string $value, string $matchBy): ?string
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->where('u.deletedAt IS NULL')
        ;

        match ($matchBy) {
            'email' => $qb->andWhere('u.email = :value'),
            default => throw new InvalidArgumentException("Неизвестный matchBy: {$matchBy}"),
        };

        /** @var null|UserOrmEntity $user */
        $user = $qb->setParameter('value', $value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $user?->getId();
    }
}
