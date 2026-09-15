<?php

namespace App\Service;

use App\Document\Statistics;
use App\Repository\BookingRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

class StatisticsService
{
    private const DAY_NAMES = [
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
        7 => 'sunday',
    ];

    public function __construct(
        private BookingRepository $bookingRepository,
        private RestaurantRepository $restaurantRepository,
        private DocumentManager $documentManager
    ) {
    }

    /**
     * Calcule et enregistre les statistiques du restaurant.
     *
     * @return Statistics
     */
    public function generateStatistics(): Statistics
    {
        $restaurant =
            $this->restaurantRepository->findOneBy([
                'name' => 'Quai Antique',
            ]);

        if ($restaurant === null) {
            throw new \RuntimeException(
                'Le restaurant Quai Antique est introuvable.'
            );
        }

        $maxGuest =
            $restaurant->getMaxGuest();

        if ($maxGuest === null || $maxGuest <= 0) {
            throw new \RuntimeException(
                'La capacité maximale du restaurant est invalide.'
            );
        }

        $bookings =
            $this->bookingRepository->findBy(
                [
                    'restaurant' => $restaurant,
                ],
                [
                    'bookingDate' => 'ASC',
                    'bookingTime' => 'ASC',
                ]
            );

        $statistics =
            $this->findExistingStatistics();

        if ($statistics === null) {
            $statistics =
                new Statistics();
        }

        $statistics->setGeneratedAt(
            new \DateTime()
        );

        $statistics->setAverageGuestsPerBooking(
            $this->calculateAverageGuests(
                $bookings
            )
        );

        $statistics->setAverageOccupancyByDay(
            $this->calculateAverageOccupancyByDay(
                $bookings,
                $maxGuest
            )
        );

        $statistics->setBusiestTimeSlot(
            $this->calculateBusiestTimeSlot(
                $bookings
            )
        );

        $this->documentManager->persist(
            $statistics
        );

        $this->documentManager->flush();

        return $statistics;
    }

    /**
     * Récupère le document de statistiques existant.
     *
     * @return Statistics|null
     */
    private function findExistingStatistics(): ?Statistics
    {
        return $this->documentManager
            ->getRepository(Statistics::class)
            ->findOneBy([]);
    }

    /**
     * Calcule le nombre moyen de convives par réservation.
     *
     * @param array $bookings
     * @return float
     */
    private function calculateAverageGuests(
        array $bookings
    ): float {
        if (count($bookings) === 0) {
            return 0.0;
        }

        $totalGuests = 0;

        foreach ($bookings as $booking) {
            $guestNumber =
                $booking->getGuestNumber();

            if ($guestNumber === null) {
                continue;
            }

            $totalGuests += $guestNumber;
        }

        return round(
            $totalGuests / count($bookings),
            2
        );
    }

    /**
     * Calcule le taux d'occupation moyen par jour.
     *
     * Les réservations sont regroupées par jour de la semaine
     * et par créneau horaire.
     *
     * @param array $bookings
     * @param int $maxGuest
     * @return array
     */
    private function calculateAverageOccupancyByDay(
        array $bookings,
        int $maxGuest
    ): array {
        $occupancyByDay = [];

        foreach (self::DAY_NAMES as $dayName) {
            $occupancyByDay[$dayName] = [];
        }

        foreach ($bookings as $booking) {
            $bookingDate =
                $booking->getBookingDate();

            $bookingTime =
                $booking->getBookingTime();

            $guestNumber =
                $booking->getGuestNumber();

            if (
                $bookingDate === null
                || $bookingTime === null
                || $guestNumber === null
            ) {
                continue;
            }

            $dayNumber =
                (int) $bookingDate->format('N');

            $dayName =
                self::DAY_NAMES[$dayNumber];

            $time =
                $bookingTime->format('H:i');

            if (!isset($occupancyByDay[$dayName][$time])) {
                $occupancyByDay[$dayName][$time] = 0;
            }

            $occupancyByDay[$dayName][$time] +=
                $guestNumber;
        }

        $averageByDay = [];

        foreach ($occupancyByDay as $dayName => $slots) {
            if (count($slots) === 0) {
                $averageByDay[$dayName] = 0.0;

                continue;
            }

            $totalOccupancy = 0.0;

            foreach ($slots as $guestNumber) {
                $occupancy =
                    ($guestNumber / $maxGuest) * 100;

                $totalOccupancy += $occupancy;
            }

            $averageByDay[$dayName] =
                round(
                    $totalOccupancy / count($slots),
                    2
                );
        }

        return $averageByDay;
    }

    /**
     * Recherche le créneau ayant accueilli le plus de clients.
     *
     * @param array $bookings
     * @return array
     */
    private function calculateBusiestTimeSlot(
        array $bookings
    ): array {
        $guestsByTimeSlot = [];

        foreach ($bookings as $booking) {
            $bookingTime =
                $booking->getBookingTime();

            $guestNumber =
                $booking->getGuestNumber();

            if (
                $bookingTime === null
                || $guestNumber === null
            ) {
                continue;
            }

            $time =
                $bookingTime->format('H:i');

            if (!isset($guestsByTimeSlot[$time])) {
                $guestsByTimeSlot[$time] = 0;
            }

            $guestsByTimeSlot[$time] +=
                $guestNumber;
        }

        if (count($guestsByTimeSlot) === 0) {
            return [
                'time' => null,
                'guests' => 0,
            ];
        }

        arsort($guestsByTimeSlot);

        $time =
            array_key_first($guestsByTimeSlot);

        return [
            'time' => $time,
            'guests' => $guestsByTimeSlot[$time],
        ];
    }
}