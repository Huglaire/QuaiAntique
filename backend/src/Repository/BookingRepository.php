<?php

namespace App\Repository;

use DateTimeInterface;
use App\Entity\Booking;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
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
     * Retourne une réservation appartenant à un utilisateur
     * à partir de son UUID.
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

    /**
     * Retourne le nombre total de couverts réservés
     * pour une date et une plage horaire données.
     *
     * Une réservation peut être exclue du calcul.
     * Cela permet notamment de vérifier la capacité
     * lors de la modification d'une réservation existante.
     */
    public function countGuestsForService(
        DateTimeInterface $bookingDate,
        DateTimeInterface $serviceOpeningTime,
        DateTimeInterface $serviceClosingTime,
        ?Booking $excludedBooking = null
    ): int {
        $queryBuilder = $this->createQueryBuilder('b')
            ->select('COALESCE(SUM(b.guestNumber), 0)')
            ->andWhere('b.bookingDate = :bookingDate')
            ->andWhere('b.bookingTime >= :serviceOpeningTime')
            ->andWhere('b.bookingTime <= :serviceClosingTime')
            ->setParameter(
                'bookingDate',
                $bookingDate,
                Types::DATE_MUTABLE
            )
            ->setParameter(
                'serviceOpeningTime',
                $serviceOpeningTime,
                Types::TIME_MUTABLE
            )
            ->setParameter(
                'serviceClosingTime',
                $serviceClosingTime,
                Types::TIME_MUTABLE
            );

        // Lors d'une modification, exclut la réservation actuelle
        // afin qu'elle ne soit pas comptée deux fois.
        if ($excludedBooking !== null) {
            $queryBuilder
                ->andWhere('b.id != :excludedBookingId')
                ->setParameter(
                    'excludedBookingId',
                    $excludedBooking->getId()
                );
        }

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }
}