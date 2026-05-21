<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с клиентами через Doctrine ORM.
 *
 * @extends ServiceEntityRepository<Client>
 *
 * @method null|Client find($id, $lockMode = null, $lockVersion = null)
 * @method null|Client findOneBy(array $criteria, ?array $orderBy = null)
 * @method Client[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function save(Client $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Client $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return Client[] */
    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->andWhere('c.deletedAt IS NULL')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Поиск клиентов с пагинацией и фильтрацией.
     *
     * @param array<string, mixed> $criteria
     *
     * @return Client[]
     */
    public function findAllPaginated(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.deletedAt IS NULL')
        ;

        if (isset($criteria['name']) && \is_string($criteria['name'])) {
            $qb->andWhere('c.name LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%')
            ;
        }

        return $qb
            ->orderBy('c.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Подсчёт клиентов с учётом фильтров.
     *
     * @param array<string, mixed> $criteria
     */
    public function countAll(array $criteria): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.deletedAt IS NULL')
        ;

        if (isset($criteria['name']) && \is_string($criteria['name'])) {
            $qb->andWhere('c.name LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%')
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
