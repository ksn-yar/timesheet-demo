<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\ImportPolicy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с политиками импорта через Doctrine ORM.
 *
 * @extends ServiceEntityRepository<ImportPolicy>
 *
 * @method null|ImportPolicy find($id, $lockMode = null, $lockVersion = null)
 * @method null|ImportPolicy findOneBy(array $criteria, ?array $orderBy = null)
 * @method ImportPolicy[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class ImportPolicyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportPolicy::class);
    }

    public function save(ImportPolicy $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ImportPolicy $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Поиск политик импорта с пагинацией.
     *
     * @param array<string, mixed> $criteria
     *
     * @return ImportPolicy[]
     */
    public function findAllPaginated(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('ip');

        return $qb
            ->orderBy('ip.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Подсчёт политик импорта.
     *
     * @param array<string, mixed> $criteria
     */
    public function countAll(array $criteria): int
    {
        return (int) $this->createQueryBuilder('ip')
            ->select('COUNT(ip.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** Поиск активной политики по системе-источнику. */
    public function findActiveBySourceSystem(string $sourceSystem): ?ImportPolicy
    {
        return $this->createQueryBuilder('ip')
            ->andWhere('ip.sourceSystem = :sourceSystem')
            ->andWhere('ip.isActive = :isActive')
            ->setParameter('sourceSystem', $sourceSystem)
            ->setParameter('isActive', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
