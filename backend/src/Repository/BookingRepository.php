<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * Retourne toutes les réservations d'un utilisateur.
     *
     * Les réservations sont classées de la plus récente
     * à la plus ancienne.
     *
     * @return Booking[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->setParameter('user', $user)
            ->orderBy('b.bookingDate', 'DESC')
            ->addOrderBy('b.bookingTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne une réservation appartenant à un utilisateur à partir de son UUID.
     */
    public function findOneByUuidAndUser(
        string $uuid,
        User $user
    ): ?Booking {
        return $this->createQueryBuilder('b')
            ->andWhere('b.uuid = :uuid')
            ->andWhere('b.user = :user')
            ->setParameter('uuid', $uuid)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
