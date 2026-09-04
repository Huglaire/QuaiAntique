<?php

namespace App\DataFixtures;

use App\Entity\Booking;
use App\Repository\RestaurantRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class BookingFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private RestaurantRepository $restaurantRepository
    ) {
    }

    /**
     * Indique les fixtures qui doivent être exécutées avant celle-ci.
     */
    public function getDependencies(): array
    {
        return [
            AdminFixtures::class,
            UserFixtures::class,
            RestaurantFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Récupère le restaurant de test.
        $restaurant = $this->restaurantRepository->findOneBy([
            'name' => 'Quai Antique',
        ]);

        if ($restaurant === null) {
            throw new \RuntimeException(
                'Le restaurant Quai Antique est introuvable.'
            );
        }

        // Récupère l'utilisateur de test.
        $user = $this->userRepository->findOneBy([
            'email' => 'user@mail.fr',
        ]);

        if ($user === null) {
            throw new \RuntimeException(
                'L\'utilisateur user@mail.fr est introuvable.'
            );
        }

        // Récupère l'administrateur de test.
        $admin = $this->userRepository->findOneBy([
            'email' => 'admin@mail.fr',
        ]);

        if ($admin === null) {
            throw new \RuntimeException(
                'L\'administrateur admin@mail.fr est introuvable.'
            );
        }

        // Crée les trois réservations de test.
        $bookings = [
            [
                'user' => $user,
                'guestNumber' => 2,
                'date' => '2026-09-08',
                'time' => '12:00',
                'allergy' => null,
            ],
            [
                'user' => $user,
                'guestNumber' => 3,
                'date' => '2026-09-09',
                'time' => '20:00',
                'allergy' => null,
            ],
            [
                'user' => $admin,
                'guestNumber' => 4,
                'date' => '2026-09-10',
                'time' => '19:30',
                'allergy' => 'Allergie aux crustacés',
            ],
        ];

        foreach ($bookings as $bookingData) {
            $booking = new Booking();

            // Génère automatiquement un UUID unique.
            $booking->setUuid(
                Uuid::v4()->toRfc4122()
            );

            // Informations de la réservation.
            $booking->setGuestNumber(
                $bookingData['guestNumber']
            );

            $booking->setBookingDate(
                new \DateTime($bookingData['date'])
            );

            $booking->setBookingTime(
                new \DateTime($bookingData['time'])
            );

            $booking->setAllergy(
                $bookingData['allergy']
            );

            // Associe la réservation à son utilisateur.
            $booking->setUser(
                $bookingData['user']
            );

            // Associe la réservation au restaurant.
            $booking->setRestaurant($restaurant);

            // Enregistre la date de création.
            $booking->setCreatedAt(
                new \DateTimeImmutable()
            );

            // Prépare la réservation pour son enregistrement.
            $manager->persist($booking);
        }

        // Enregistre les réservations en base.
        $manager->flush();
    }
}