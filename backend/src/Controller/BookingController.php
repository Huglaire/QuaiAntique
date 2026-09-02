<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Restaurant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;
use App\Entity\User;

final class BookingController
{
    #[Route('/api/bookings', name: 'api_booking_create', methods: ['POST'])]
    public function create(
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifie qu'un utilisateur authentifié est disponible.
        if ($user === null) {
            return new JsonResponse([
                'message' => 'Utilisateur non authentifié.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Récupère les données JSON envoyées par le frontend.
        $data = json_decode($request->getContent(), true);

        // Vérifie que les données reçues sont bien au format attendu.
        if (!is_array($data)) {
            return new JsonResponse([
                'message' => 'Les données envoyées sont invalides.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie la présence des champs obligatoires.
        $requiredFields = [
            'guestNumber',
            'bookingDate',
            'bookingTime',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return new JsonResponse([
                    'message' => sprintf(
                        'Le champ "%s" est obligatoire.',
                        $field
                    )
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Vérifie que le nombre de convives est un nombre entier positif.
        if (
            !is_numeric($data['guestNumber'])
            || (int) $data['guestNumber'] <= 0
        ) {
            return new JsonResponse([
                'message' => 'Le nombre de convives doit être un entier positif.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $guestNumber = (int) $data['guestNumber'];

        // Récupère le restaurant.
        // L'application ne possède actuellement qu'un restaurant.
        $restaurant = $entityManager
            ->getRepository(Restaurant::class)
            ->findOneBy([]);

        // Vérifie qu'un restaurant existe en base de données.
        if ($restaurant === null) {
            return new JsonResponse([
                'message' => 'Le restaurant est introuvable.'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifie que le nombre de convives ne dépasse pas
        // la capacité maximale du restaurant.
        if (
            $restaurant->getMaxGuest() !== null
            && $guestNumber > $restaurant->getMaxGuest()
        ) {
            return new JsonResponse([
                'message' => sprintf(
                    'Le nombre maximum de convives est de %d.',
                    $restaurant->getMaxGuest()
                )
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Convertit la date envoyée par le frontend.
        $bookingDate = \DateTime::createFromFormat(
            'Y-m-d',
            $data['bookingDate']
        );

        // Vérifie que la date respecte bien le format attendu.
        if (
            $bookingDate === false
            || $bookingDate->format('Y-m-d') !== $data['bookingDate']
        ) {
            return new JsonResponse([
                'message' => 'La date de réservation est invalide. Le format attendu est AAAA-MM-JJ.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupère la date actuelle sans tenir compte de l'heure.
        $today = new \DateTime('today');

        // Vérifie que la date de réservation n'est pas antérieure à aujourd'hui.
        if ($bookingDate < $today) {
            return new JsonResponse([
                'message' => 'La date de réservation ne peut pas être antérieure à aujourd\'hui.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Convertit l'heure envoyée par le frontend.
        $bookingTime = \DateTime::createFromFormat(
            'H:i',
            $data['bookingTime']
        );

        // Vérifie que l'heure respecte bien le format attendu.
        if (
            $bookingTime === false
            || $bookingTime->format('H:i') !== $data['bookingTime']
        ) {
            return new JsonResponse([
                'message' => 'L\'heure de réservation est invalide. Le format attendu est HH:MM.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Crée une nouvelle réservation.
        $booking = new Booking();

        // Génère automatiquement un UUID unique.
        $booking->setUuid(
            Uuid::v4()->toRfc4122()
        );

        // Associe la réservation à l'utilisateur authentifié.
        $booking->setUser($user);

        // Associe la réservation au restaurant.
        $booking->setRestaurant($restaurant);

        // Enregistre les informations de la réservation.
        $booking->setGuestNumber($guestNumber);
        $booking->setBookingDate($bookingDate);
        $booking->setBookingTime($bookingTime);

        // Le champ allergie est facultatif.
        $booking->setAllergy(
            $data['allergy'] ?? null
        );

        // Enregistre la date de création.
        $booking->setCreatedAt(
            new \DateTimeImmutable()
        );

        // Enregistre la réservation en base de données.
        $entityManager->persist($booking);
        $entityManager->flush();

        // Retourne les informations de la réservation créée.
        return new JsonResponse([
            'message' => 'Réservation créée avec succès.',
            'booking' => [
                'uuid' => $booking->getUuid(),
                'guestNumber' => $booking->getGuestNumber(),
                'bookingDate' => $booking->getBookingDate()?->format('Y-m-d'),
                'bookingTime' => $booking->getBookingTime()?->format('H:i'),
                'allergy' => $booking->getAllergy(),
                'createdAt' => $booking->getCreatedAt()?->format(
                    \DateTimeInterface::ATOM
                ),
                'restaurant' => [
                    'uuid' => $restaurant->getUuid(),
                    'name' => $restaurant->getName(),
                ],
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}