<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RefreshTokenEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RefreshTokenRepository extends ServiceEntityRepository
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @psalm-suppress PossiblyUnusedParam
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshTokenEntity::class);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @psalm-suppress PossiblyUnusedParam
     */
    public function persist(RefreshTokenEntity $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @psalm-suppress PossiblyUnusedParam
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @psalm-suppress PossiblyUnusedParam
     */
    public function save(RefreshTokenEntity $entity): void
    {
        $this->persist($entity);
        $this->flush();
    }
}
