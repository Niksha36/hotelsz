<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\HouseEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HouseRepository extends ServiceEntityRepository
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @psalm-suppress PossiblyUnusedParam
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HouseEntity::class);
    }

    /**
     * @psalm-suppress PossiblyUnusedParam
     */
    public function save(HouseEntity $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @psalm-suppress PossiblyUnusedParam
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function remove(HouseEntity $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
