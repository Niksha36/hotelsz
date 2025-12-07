<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BookingEntity;
use App\Entity\HouseEntity;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @psalm-suppress PossiblyUnusedParam
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookingEntity::class);
    }

    /**
     * @psalm-suppress PossiblyUnusedParam
     */
    public function save(BookingEntity $entity, bool $flush = true): BookingEntity
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
        return $entity;
    }

    /**
     * @psalm-suppress PossiblyUnusedParam
     */
    public function remove(BookingEntity $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @psalm-suppress PossiblyUnusedParam
     */
    public function isHouseBookedForThePeriod(int $houseId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): bool
    {
        $qb = $this->createQueryBuilder('b');

        $count = $qb->select('count(b.id)')
            ->where('b.house = :houseId')
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->lt('b.dateFrom', ':endDate'),
                    $qb->expr()->gt('b.dateTo', ':startDate')
                )
            )
            ->setParameter('houseId', $houseId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @param DateTimeImmutable $startDate
     * @param DateTimeImmutable $endDate
     * @return HouseEntity[]
     * @psalm-suppress PossiblyUnusedParam
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getHousesAvailableForThePeriod(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        $bookedHousesSubQuery = $this->createQueryBuilder('b')
            ->select('IDENTITY(b.house)')
            ->where('b.dateFrom < :endDate')
            ->andWhere('b.dateTo > :startDate');

        $qb = $this->getEntityManager()->createQueryBuilder();

        return $qb->select('h')
            ->from(HouseEntity::class, 'h')
            ->where($qb->expr()->notIn('h.id', $bookedHousesSubQuery->getDQL()))
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }
}
