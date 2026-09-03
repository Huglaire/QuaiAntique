<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/bookings')]
#[IsGranted('ROLE_ADMIN')]
class AdminBookingController extends AbstractController
{
    /**
     * Retourne toutes les réservations ou celles d'une date donnée.
     */
    #[Route('', name: 'app_admin_bookings', methods: ['GET'])]
    public function index(
        Request $request,
        BookingRepository $bookingRepository
    ): JsonResponse {
        // Récupère le paramètre "date" dans l'URL.
        $date = $request->query->get('date');

        // Si aucune date n'est fournie, récupère toutes les réservations.
        if ($date === null) {
            $bookings = $bookingRepository->findAll();
        } else {
            // Vérifie que la date respecte le format attendu.
            $bookingDate = \DateTime::createFromFormat(
                'Y-m-d',
                $date
            );

            // Vérifie également que la date est réellement valide.
            if (
                $bookingDate === false
                || $bookingDate->format('Y-m-d') !== $date
            ) {
                return $this->json([
                    'message' => 'Le format de la date doit être YYYY-MM-DD.',
                ], 400);
            }

            // Récupère uniquement les réservations de cette date.
            $bookings = $bookingRepository->findByDate(
                $bookingDate
            );
        }

        // Prépare les données à retourner dans la réponse JSON.
        $data = array_map(
            function (Booking $booking): array {
                return [
                    'uuid' => $booking->getUuid(),
                    'guestNumber' => $booking->getGuestNumber(),
                    'bookingDate' => $booking->getBookingDate()?->format('Y-m-d'),
                    'bookingTime' => $booking->getBookingTime()?->format('H:i'),
                    'allergy' => $booking->getAllergy(),
                    'user' => [
                        'uuid' => $booking->getUser()?->getUuid(),
                        'firstName' => $booking->getUser()?->getFirstName(),
                        'lastName' => $booking->getUser()?->getLastName(),
                        'email' => $booking->getUser()?->getEmail(),
                    ],
                ];
            },
            $bookings
        );

        return $this->json($data);
    }

    /**
     * Modifie une réservation en tant qu'administrateur.
     */
    #[Route('/{uuid}', name: 'app_admin_booking_update', methods: ['PATCH'])]
    public function update(
        string $uuid,
        Request $request,
        BookingRepository $bookingRepository,
        RestaurantRepository $restaurantRepository
    ): JsonResponse {
        // Vérifie que la réservation existe.
        $booking = $bookingRepository->findOneBy([
            'uuid' => $uuid,
        ]);

        if ($booking === null) {
            return $this->json([
                'message' => 'Réservation introuvable.',
            ], 404);
        }

        // Vérifie que le JSON envoyé est valide.
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'message' => 'Le corps de la requête doit être un JSON valide.',
            ], 400);
        }

        // Récupère le restaurant de la réservation.
        $restaurant = $booking->getRestaurant();

        if ($restaurant === null) {
            return $this->json([
                'message' => 'Restaurant introuvable.',
            ], 404);
        }

        /*
         * Nombre de convives.
         * Si le champ n'est pas envoyé, on conserve la valeur actuelle.
         */
        $guestNumber = $booking->getGuestNumber();

        if (array_key_exists('guestNumber', $data)) {
            if (
                !is_int($data['guestNumber'])
                || $data['guestNumber'] <= 0
            ) {
                return $this->json([
                    'message' => 'Le nombre de convives doit être un entier positif.',
                ], 400);
            }

            if ($data['guestNumber'] > $restaurant->getMaxGuest()) {
                return $this->json([
                    'message' => 'Le nombre de convives dépasse la capacité maximale du restaurant.',
                ], 400);
            }

            $guestNumber = $data['guestNumber'];
        }

        /*
         * Date de réservation.
         * Si elle n'est pas envoyée, on conserve la date actuelle.
         */
        $bookingDate = $booking->getBookingDate();

        if (array_key_exists('bookingDate', $data)) {
            if (
                !is_string($data['bookingDate'])
                || !preg_match(
                    '/^\d{4}-\d{2}-\d{2}$/',
                    $data['bookingDate']
                )
            ) {
                return $this->json([
                    'message' => 'La date doit respecter le format YYYY-MM-DD.',
                ], 400);
            }

            $newBookingDate = \DateTime::createFromFormat(
                'Y-m-d',
                $data['bookingDate']
            );

            if (
                $newBookingDate === false
                || $newBookingDate->format('Y-m-d') !== $data['bookingDate']
            ) {
                return $this->json([
                    'message' => 'La date de réservation est invalide.',
                ], 400);
            }

            // Le restaurant est fermé le lundi.
            if ((int) $newBookingDate->format('N') === 1) {
                return $this->json([
                    'message' => 'Le restaurant est fermé le lundi.',
                ], 400);
            }

            $bookingDate = $newBookingDate;
        }

        /*
         * Vérifie que la date de réservation n'est pas passée.
         */
        $today = new \DateTime('today');

        if ($bookingDate < $today) {
            return $this->json([
                'message' => 'La date de réservation ne peut pas être passée.',
            ], 400);
        }

        /*
         * Heure de réservation.
         * Si elle n'est pas envoyée, on conserve l'heure actuelle.
         */
        $bookingTime = $booking->getBookingTime();

        if (array_key_exists('bookingTime', $data)) {
            if (
                !is_string($data['bookingTime'])
                || !preg_match(
                    '/^\d{2}:\d{2}$/',
                    $data['bookingTime']
                )
            ) {
                return $this->json([
                    'message' => 'L\'heure doit respecter le format HH:MM.',
                ], 400);
            }

            $newBookingTime = \DateTime::createFromFormat(
                'H:i',
                $data['bookingTime']
            );

            if (
                $newBookingTime === false
                || $newBookingTime->format('H:i') !== $data['bookingTime']
            ) {
                return $this->json([
                    'message' => 'L\'heure de réservation est invalide.',
                ], 400);
            }

            $bookingTime = $newBookingTime;
        }

        // Récupère les horaires des services.
        $serviceTimes = $this->getServiceTimes($restaurant);

        // Convertit l'heure de réservation en minutes depuis minuit.
        $bookingMinutes =
            (int) $bookingTime->format('H') * 60
            + (int) $bookingTime->format('i');

        // Vérifie que l'heure appartient à un service.
        $service = null;

        foreach ($serviceTimes as $serviceName => $times) {
            // Convertit l'ouverture en minutes depuis minuit.
            $openingMinutes =
                (int) $times['opening']->format('H') * 60
                + (int) $times['opening']->format('i');

            // Convertit la fermeture en minutes depuis minuit.
            $closingMinutes =
                (int) $times['closing']->format('H') * 60
                + (int) $times['closing']->format('i');

            if (
                $bookingMinutes >= $openingMinutes
                && $bookingMinutes <= $closingMinutes
            ) {
                $service = $serviceName;
                break;
            }
        }

        if ($service === null) {
            return $this->json([
                'message' => 'L\'heure choisie ne correspond pas à un créneau de service.',
            ], 400);
        }

        // Vérifie que l'heure respecte les créneaux de 15 minutes.
        if (!$this->isValidTimeSlot($bookingTime, $serviceTimes[$service]['opening'])) {
            return $this->json([
                'message' => 'L\'heure doit être choisie par tranche de 15 minutes.',
            ], 400);
        }

        // Vérifie la capacité du service en excluant la réservation actuelle.
        if (
            !$this->checkServiceCapacity(
                $bookingRepository,
                $bookingDate,
                $serviceTimes[$service]['opening'],
                $serviceTimes[$service]['closing'],
                $guestNumber,
                $booking
            )
        ) {
            return $this->json([
                'message' => 'La capacité maximale du restaurant est atteinte pour ce service.',
            ], 400);
        }

        // Applique les modifications.
        $booking->setGuestNumber($guestNumber);
        $booking->setBookingDate($bookingDate);
        $booking->setBookingTime($bookingTime);

        // Modifie les allergies uniquement si le champ est présent.
        if (array_key_exists('allergy', $data)) {
            if (
                $data['allergy'] !== null
                && !is_string($data['allergy'])
            ) {
                return $this->json([
                    'message' => 'Le champ allergy doit être une chaîne de caractères ou null.',
                ], 400);
            }

            $booking->setAllergy($data['allergy']);
        }

        // Enregistre la date de modification.
        $booking->setUpdatedAt(
            new \DateTimeImmutable()
        );

        $bookingRepository->getEntityManager()->flush();

        return $this->json([
            'message' => 'Réservation modifiée avec succès.',
            'booking' => [
                'uuid' => $booking->getUuid(),
                'guestNumber' => $booking->getGuestNumber(),
                'bookingDate' => $booking->getBookingDate()?->format('Y-m-d'),
                'bookingTime' => $booking->getBookingTime()?->format('H:i'),
                'allergy' => $booking->getAllergy(),
            ],
        ]);
    }

    /**
     * Retourne les horaires d'ouverture des deux services.
     */
    private function getServiceTimes(
        \App\Entity\Restaurant $restaurant
    ): array {
        $lunchOpening = $restaurant->getLunchOpeningTime();
        $dinnerOpening = $restaurant->getDinnerOpeningTime();

        $lunchClosing = (clone $lunchOpening)->modify('+2 hours');
        $dinnerClosing = (clone $dinnerOpening)->modify('+2 hours');

        return [
            'lunch' => [
                'opening' => $lunchOpening,
                'closing' => $lunchClosing,
            ],
            'dinner' => [
                'opening' => $dinnerOpening,
                'closing' => $dinnerClosing,
            ],
        ];
    }

    /**
     * Vérifie qu'une heure correspond à un créneau de 15 minutes.
     */
    private function isValidTimeSlot(
        \DateTimeInterface $bookingTime,
        \DateTimeInterface $openingTime
    ): bool {
        $minutesSinceOpening =
            (
                (int) $bookingTime->format('H') * 60
                + (int) $bookingTime->format('i')
            )
            -
            (
                (int) $openingTime->format('H') * 60
                + (int) $openingTime->format('i')
            );

        return $minutesSinceOpening >= 0
            && $minutesSinceOpening % 15 === 0;
    }

    /**
     * Vérifie la capacité restante du service.
     */
    private function checkServiceCapacity(
        BookingRepository $bookingRepository,
        \DateTimeInterface $bookingDate,
        \DateTimeInterface $serviceOpening,
        \DateTimeInterface $serviceClosing,
        int $guestNumber,
        Booking $excludedBooking
    ): bool {
        $currentGuests = $bookingRepository->countGuestsForService(
            $bookingDate,
            $serviceOpening,
            $serviceClosing,
            $excludedBooking
        );

        return (
            $currentGuests + $guestNumber
        ) <= $excludedBooking->getRestaurant()->getMaxGuest();
    }

    /**
     * Supprime une réservation en tant qu'administrateur.
     */
    #[Route('/{uuid}', name: 'app_admin_booking_delete', methods: ['DELETE'])]
    public function delete(
        string $uuid,
        BookingRepository $bookingRepository
    ): JsonResponse {
        // Recherche la réservation grâce à son UUID.
        $booking = $bookingRepository->findOneBy([
            'uuid' => $uuid,
        ]);

        // Vérifie que la réservation existe.
        if ($booking === null) {
            return $this->json([
                'message' => 'Réservation introuvable.',
            ], 404);
        }

        // Supprime la réservation.
        $bookingRepository->getEntityManager()->remove($booking);
        $bookingRepository->getEntityManager()->flush();

        // Retourne une réponse sans contenu.
        return new JsonResponse(null, 204);
    }
}
