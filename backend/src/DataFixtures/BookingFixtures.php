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
        // Récupère le restaurant Quai Antique.
        $restaurant = $this->restaurantRepository->findOneBy([
            'name' => 'Quai Antique',
        ]);

        if ($restaurant === null) {
            throw new \RuntimeException(
                'Le restaurant Quai Antique est introuvable.'
            );
        }

        // Liste des adresses e-mail des dix clients de test.
        $clientEmails = [
            'user@mail.fr',
            'client2@mail.fr',
            'client3@mail.fr',
            'client4@mail.fr',
            'client5@mail.fr',
            'client6@mail.fr',
            'client7@mail.fr',
            'client8@mail.fr',
            'client9@mail.fr',
            'client10@mail.fr',
        ];

        // Récupère les utilisateurs correspondant aux clients de test.
        $users = [];

        foreach ($clientEmails as $email) {
            $user = $this->userRepository->findOneBy([
                'email' => $email,
            ]);

            if ($user === null) {
                throw new \RuntimeException(
                    "L'utilisateur {$email} est introuvable."
                );
            }

            $users[$email] = $user;
        }

        /*
         * Jeu de données volontairement contrôlé.
         *
         * Il contient 40 réservations :
         * 10 clients × 4 réservations.
         *
         * Les horaires et le nombre de convives sont définis
         * manuellement afin de pouvoir vérifier les statistiques.
         */
        $bookings = [
            // Mardi 08/09/2026.
            ['2026-09-08', '12:00', 2],
            ['2026-09-08', '12:00', 3],
            ['2026-09-08', '12:30', 4],
            ['2026-09-08', '12:30', 5],
            ['2026-09-08', '19:00', 3],
            ['2026-09-08', '19:00', 4],
            ['2026-09-08', '20:00', 6],
            ['2026-09-08', '20:00', 8],

            // Mercredi 09/09/2026.
            ['2026-09-09', '12:00', 3],
            ['2026-09-09', '12:00', 4],
            ['2026-09-09', '12:30', 4],
            ['2026-09-09', '12:30', 5],
            ['2026-09-09', '19:00', 4],
            ['2026-09-09', '19:00', 5],
            ['2026-09-09', '20:00', 7],
            ['2026-09-09', '20:00', 8],

            // Jeudi 10/09/2026.
            ['2026-09-10', '12:00', 2],
            ['2026-09-10', '12:00', 5],
            ['2026-09-10', '12:30', 5],
            ['2026-09-10', '12:30', 6],
            ['2026-09-10', '19:00', 4],
            ['2026-09-10', '19:00', 5],
            ['2026-09-10', '20:00', 8],
            ['2026-09-10', '20:00', 9],

            // Vendredi 11/09/2026.
            ['2026-09-11', '12:00', 4],
            ['2026-09-11', '12:00', 5],
            ['2026-09-11', '12:30', 6],
            ['2026-09-11', '12:30', 7],
            ['2026-09-11', '19:00', 6],
            ['2026-09-11', '19:00', 7],
            ['2026-09-11', '20:00', 9],
            ['2026-09-11', '20:00', 10],

            // Samedi 12/09/2026.
            ['2026-09-12', '12:00', 3],
            ['2026-09-12', '12:00', 4],
            ['2026-09-12', '12:30', 5],
            ['2026-09-12', '12:30', 6],
            ['2026-09-12', '19:00', 7],
            ['2026-09-12', '19:00', 8],
            ['2026-09-12', '20:00', 10],
            ['2026-09-12', '20:00', 10],
        ];

        /*
         * Attribution des réservations aux clients.
         *
         * La répartition cyclique garantit que chaque client
         * reçoit exactement quatre réservations.
         */
        foreach ($bookings as $index => $bookingData) {
            $clientEmail =
                $clientEmails[$index % count($clientEmails)];

            $booking = new Booking();

            // Génère automatiquement un UUID unique.
            $booking->setUuid(
                Uuid::v4()->toRfc4122()
            );

            // Définit le nombre de convives.
            $booking->setGuestNumber(
                $bookingData[2]
            );

            // Définit la date de réservation.
            $booking->setBookingDate(
                new \DateTime($bookingData[0])
            );

            // Définit l'heure de réservation.
            $booking->setBookingTime(
                new \DateTime($bookingData[1])
            );

            // Aucune allergie par défaut.
            $booking->setAllergy(null);

            // Associe la réservation à son client.
            $booking->setUser(
                $users[$clientEmail]
            );

            // Associe la réservation au restaurant.
            $booking->setRestaurant(
                $restaurant
            );

            // Enregistre la date de création.
            $booking->setCreatedAt(
                new \DateTimeImmutable()
            );

            // Prépare la réservation pour son enregistrement.
            $manager->persist($booking);
        }

        // Enregistre toutes les réservations en base.
        $manager->flush();
    }
}