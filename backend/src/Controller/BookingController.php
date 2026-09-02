<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final class BookingController
{
    /**
     * Crée une nouvelle réservation.
     */
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

    /**
     * Retourne les créneaux disponibles pour une date donnée.
     */
    #[Route(
        '/api/bookings/availability',
        name: 'api_booking_availability',
        methods: ['GET']
    )]
    public function availability(
        Request $request,
        RestaurantRepository $restaurantRepository,
        BookingRepository $bookingRepository
    ): JsonResponse {
        // Récupère la date demandée dans l'URL.
        $date = $request->query->get('date');

        // Récupère le nombre de convives demandé.
        $guestNumber = $request->query->get('guestNumber');

        // Vérifie que les paramètres sont présents.
        if (!$date || !$guestNumber) {
            return new JsonResponse([
                'message' => 'La date et le nombre de convives sont obligatoires.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie que le nombre de convives est bien un entier positif.
        if (
            !ctype_digit((string) $guestNumber)
            || (int) $guestNumber <= 0
        ) {
            return new JsonResponse([
                'message' => 'Le nombre de convives doit être un entier positif.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $guestNumber = (int) $guestNumber;

        // Vérifie le format de la date.
        $bookingDate = \DateTime::createFromFormat(
            'Y-m-d',
            $date
        );

        if (
            !$bookingDate
            || $bookingDate->format('Y-m-d') !== $date
        ) {
            return new JsonResponse([
                'message' => 'La date doit être au format YYYY-MM-DD.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie que la date n'est pas passée.
        $today = new \DateTime('today');

        if ($bookingDate < $today) {
            return new JsonResponse([
                'message' => 'Impossible de consulter les disponibilités d’une date passée.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Le restaurant est actuellement unique dans l'application.
        $restaurant = $restaurantRepository->findOneBy([]);

        if (!$restaurant) {
            return new JsonResponse([
                'message' => 'Restaurant introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifie que le nombre de convives demandé
        // ne dépasse pas la capacité maximale du restaurant.
        if (
            $restaurant->getMaxGuest() !== null
            && $guestNumber > $restaurant->getMaxGuest()
        ) {
            return new JsonResponse([
                'message' => sprintf(
                    'Le nombre maximum de convives est de %d.',
                    $restaurant->getMaxGuest()
                ),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Le restaurant est fermé le lundi.
        if ((int) $bookingDate->format('N') === 1) {
            return new JsonResponse([
                'date' => $date,
                'lunch' => [],
                'dinner' => [],
            ], JsonResponse::HTTP_OK);
        }

        // Récupère les horaires des deux services.
        $lunchOpeningTime = $restaurant->getLunchOpeningTime();
        $dinnerOpeningTime = $restaurant->getDinnerOpeningTime();

        // Prépare la réponse.
        $response = [
            'date' => $date,
            'lunch' => [],
            'dinner' => [],
        ];

        /*
         * Vérification de la disponibilité du service du midi.
         */
        if ($lunchOpeningTime) {
            // Chaque service dure deux heures.
            $lunchClosingTime = (clone $lunchOpeningTime)
                ->modify('+2 hours');

            // Calcule le nombre de couverts déjà réservés
            // pendant le service du midi.
            $reservedGuests = $bookingRepository->countGuestsForService(
                $bookingDate,
                $lunchOpeningTime,
                $lunchClosingTime
            );

            // Vérifie si la nouvelle réservation peut être ajoutée
            // sans dépasser la capacité maximale du restaurant.
            $serviceAvailable =
                ($reservedGuests + $guestNumber)
                <= $restaurant->getMaxGuest();

            // Génère les créneaux de 15 minutes.
            $slots = $this->generateTimeSlots(
                $lunchOpeningTime,
                $lunchClosingTime
            );

            // Ajoute chaque créneau à la réponse.
            foreach ($slots as $slot) {
                $response['lunch'][] = [
                    'time' => $slot->format('H:i'),
                    'available' => $serviceAvailable,
                ];
            }
        }

        /*
         * Vérification de la disponibilité du service du soir.
         */
        if ($dinnerOpeningTime) {
            // Chaque service dure deux heures.
            $dinnerClosingTime = (clone $dinnerOpeningTime)
                ->modify('+2 hours');

            // Calcule le nombre de couverts déjà réservés
            // pendant le service du soir.
            $reservedGuests = $bookingRepository->countGuestsForService(
                $bookingDate,
                $dinnerOpeningTime,
                $dinnerClosingTime
            );

            // Vérifie si la nouvelle réservation peut être ajoutée
            // sans dépasser la capacité maximale du restaurant.
            $serviceAvailable =
                ($reservedGuests + $guestNumber)
                <= $restaurant->getMaxGuest();

            // Génère les créneaux de 15 minutes.
            $slots = $this->generateTimeSlots(
                $dinnerOpeningTime,
                $dinnerClosingTime
            );

            // Ajoute chaque créneau à la réponse.
            foreach ($slots as $slot) {
                $response['dinner'][] = [
                    'time' => $slot->format('H:i'),
                    'available' => $serviceAvailable,
                ];
            }
        }

        return new JsonResponse(
            $response,
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Retourne toutes les réservations de l'utilisateur connecté.
     */
    #[Route('/api/bookings', name: 'api_booking_list', methods: ['GET'])]
    public function list(
        #[CurrentUser] ?User $user,
        BookingRepository $bookingRepository
    ): JsonResponse {
        // Vérifie qu'un utilisateur authentifié est disponible.
        if ($user === null) {
            return new JsonResponse([
                'message' => 'Utilisateur non authentifié.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Récupère uniquement les réservations appartenant
        // à l'utilisateur connecté.
        $bookings = $bookingRepository->findByUser($user);

        // Prépare les données à retourner au frontend.
        $result = [];

        foreach ($bookings as $booking) {
            $restaurant = $booking->getRestaurant();

            $result[] = [
                'uuid' => $booking->getUuid(),
                'guestNumber' => $booking->getGuestNumber(),
                'bookingDate' => $booking->getBookingDate()?->format('Y-m-d'),
                'bookingTime' => $booking->getBookingTime()?->format('H:i'),
                'allergy' => $booking->getAllergy(),
                'createdAt' => $booking->getCreatedAt()?->format(
                    \DateTimeInterface::ATOM
                ),
                'restaurant' => [
                    'uuid' => $restaurant?->getUuid(),
                    'name' => $restaurant?->getName(),
                ],
            ];
        }

        // Retourne les réservations de l'utilisateur.
        return new JsonResponse([
            'bookings' => $result,
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Modifie une réservation de l'utilisateur connecté.
     */
    #[Route(
        '/api/bookings/{uuid}',
        name: 'api_booking_update',
        methods: ['PATCH']
    )]
    public function update(
        string $uuid,
        Request $request,
        #[CurrentUser] ?User $user,
        BookingRepository $bookingRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifie qu'un utilisateur authentifié est disponible.
        if ($user === null) {
            return new JsonResponse([
                'message' => 'Utilisateur non authentifié.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Vérifie que l'UUID fourni est valide.
        if (!Uuid::isValid($uuid)) {
            return new JsonResponse([
                'message' => 'L\'UUID de la réservation est invalide.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Recherche la réservation uniquement parmi celles
        // appartenant à l'utilisateur connecté.
        $booking = $bookingRepository->findOneByUuidAndUser(
            $uuid,
            $user
        );

        // Vérifie que la réservation existe et appartient bien
        // à l'utilisateur connecté.
        if ($booking === null) {
            return new JsonResponse([
                'message' => 'Réservation introuvable.'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupère les données JSON envoyées par le frontend.
        $data = json_decode($request->getContent(), true);

        // Vérifie que les données reçues sont bien au format attendu.
        if (!is_array($data)) {
            return new JsonResponse([
                'message' => 'Les données envoyées sont invalides.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupère le restaurant associé à la réservation.
        $restaurant = $booking->getRestaurant();

        // Vérifie que le restaurant existe.
        if ($restaurant === null) {
            return new JsonResponse([
                'message' => 'Le restaurant est introuvable.'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Modifie le nombre de convives s'il est présent.
        if (array_key_exists('guestNumber', $data)) {
            if (
                !is_numeric($data['guestNumber'])
                || (int) $data['guestNumber'] <= 0
            ) {
                return new JsonResponse([
                    'message' => 'Le nombre de convives doit être un entier positif.'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $guestNumber = (int) $data['guestNumber'];

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

            $booking->setGuestNumber($guestNumber);
        }

        // Modifie la date si elle est présente.
        if (array_key_exists('bookingDate', $data)) {
            if ($data['bookingDate'] === '') {
                return new JsonResponse([
                    'message' => 'La date de réservation est invalide.'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $bookingDate = \DateTime::createFromFormat(
                'Y-m-d',
                $data['bookingDate']
            );

            // Vérifie le format de la date.
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

            // Vérifie que la nouvelle date n'est pas antérieure à aujourd'hui.
            if ($bookingDate < $today) {
                return new JsonResponse([
                    'message' => 'La date de réservation ne peut pas être antérieure à aujourd\'hui.'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $booking->setBookingDate($bookingDate);
        }

        // Modifie l'heure si elle est présente.
        if (array_key_exists('bookingTime', $data)) {
            if ($data['bookingTime'] === '') {
                return new JsonResponse([
                    'message' => 'L\'heure de réservation est invalide.'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $bookingTime = \DateTime::createFromFormat(
                'H:i',
                $data['bookingTime']
            );

            // Vérifie le format de l'heure.
            if (
                $bookingTime === false
                || $bookingTime->format('H:i') !== $data['bookingTime']
            ) {
                return new JsonResponse([
                    'message' => 'L\'heure de réservation est invalide. Le format attendu est HH:MM.'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $booking->setBookingTime($bookingTime);
        }

        // Modifie les allergies si le champ est présent.
        if (array_key_exists('allergy', $data)) {
            $booking->setAllergy(
                $data['allergy'] === null
                    ? null
                    : (string) $data['allergy']
            );
        }

        // Met à jour la date de modification.
        $booking->setUpdatedAt(
            new \DateTimeImmutable()
        );

        // Enregistre les modifications.
        $entityManager->flush();

        // Retourne les informations de la réservation modifiée.
        return new JsonResponse([
            'message' => 'Réservation modifiée avec succès.',
            'booking' => [
                'uuid' => $booking->getUuid(),
                'guestNumber' => $booking->getGuestNumber(),
                'bookingDate' => $booking->getBookingDate()?->format('Y-m-d'),
                'bookingTime' => $booking->getBookingTime()?->format('H:i'),
                'allergy' => $booking->getAllergy(),
                'createdAt' => $booking->getCreatedAt()?->format(
                    \DateTimeInterface::ATOM
                ),
                'updatedAt' => $booking->getUpdatedAt()?->format(
                    \DateTimeInterface::ATOM
                ),
                'restaurant' => [
                    'uuid' => $restaurant->getUuid(),
                    'name' => $restaurant->getName(),
                ],
            ],
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Supprime une réservation de l'utilisateur connecté.
     */
    #[Route(
        '/api/bookings/{uuid}',
        name: 'api_booking_delete',
        methods: ['DELETE']
    )]
    public function delete(
        string $uuid,
        #[CurrentUser] ?User $user,
        BookingRepository $bookingRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifie qu'un utilisateur authentifié est disponible.
        if ($user === null) {
            return new JsonResponse([
                'message' => 'Utilisateur non authentifié.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Vérifie que l'UUID fourni est valide.
        if (!Uuid::isValid($uuid)) {
            return new JsonResponse([
                'message' => 'L\'UUID de la réservation est invalide.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Recherche la réservation uniquement parmi celles
        // appartenant à l'utilisateur connecté.
        $booking = $bookingRepository->findOneByUuidAndUser(
            $uuid,
            $user
        );

        // Vérifie que la réservation existe et appartient bien
        // à l'utilisateur connecté.
        if ($booking === null) {
            return new JsonResponse([
                'message' => 'Réservation introuvable.'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Supprime la réservation.
        $entityManager->remove($booking);
        $entityManager->flush();

        // Confirme la suppression.
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Détermine le service correspondant à une heure donnée.
     *
     * Retourne les horaires d'ouverture et de fermeture du service,
     * ou null si l'heure ne correspond à aucun service.
     */
    private function getServiceTimes(
        Restaurant $restaurant,
        \DateTimeInterface $bookingTime
    ): ?array {
        // Récupère l'heure d'ouverture du service du midi.
        $lunchOpeningTime = $restaurant->getLunchOpeningTime();

        // Récupère l'heure d'ouverture du service du soir.
        $dinnerOpeningTime = $restaurant->getDinnerOpeningTime();

        // Vérifie que les deux horaires sont bien configurés.
        if (
            $lunchOpeningTime === null
            || $dinnerOpeningTime === null
        ) {
            return null;
        }

        // Crée l'heure de fermeture du service du midi.
        $lunchClosingTime = (clone $lunchOpeningTime)
            ->modify('+2 hours');

        // Crée l'heure de fermeture du service du soir.
        $dinnerClosingTime = (clone $dinnerOpeningTime)
            ->modify('+2 hours');

        // Convertit les heures en chaînes pour faciliter la comparaison.
        $requestedTime = $bookingTime->format('H:i');
        $lunchOpening = $lunchOpeningTime->format('H:i');
        $lunchClosing = $lunchClosingTime->format('H:i');
        $dinnerOpening = $dinnerOpeningTime->format('H:i');
        $dinnerClosing = $dinnerClosingTime->format('H:i');

        // Vérifie si l'heure appartient au service du midi.
        if (
            $requestedTime >= $lunchOpening
            && $requestedTime <= $lunchClosing
        ) {
            return [
                'opening' => $lunchOpeningTime,
                'closing' => $lunchClosingTime,
            ];
        }

        // Vérifie si l'heure appartient au service du soir.
        if (
            $requestedTime >= $dinnerOpening
            && $requestedTime <= $dinnerClosing
        ) {
            return [
                'opening' => $dinnerOpeningTime,
                'closing' => $dinnerClosingTime,
            ];
        }

        // L'heure ne correspond à aucun service.
        return null;
    }

    /**
     * Génère les créneaux de réservation d'un service.
     *
     * Les créneaux sont espacés de 15 minutes.
     * L'heure de fermeture est incluse.
     *
     * @return \DateTime[]
     */
    private function generateTimeSlots(
        \DateTimeInterface $openingTime,
        \DateTimeInterface $closingTime
    ): array {
        // Initialise le tableau qui contiendra les créneaux.
        $slots = [];

        // Commence à l'heure d'ouverture du service.
        $currentTime = clone $openingTime;

        // Génère les créneaux jusqu'à l'heure de fermeture incluse.
        while ($currentTime <= $closingTime) {
            // Ajoute le créneau actuel au tableau.
            $slots[] = clone $currentTime;

            // Passe au créneau suivant, 15 minutes plus tard.
            $currentTime->modify('+15 minutes');
        }

        return $slots;
    }
}