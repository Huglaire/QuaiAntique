<?php

namespace App\Controller;

use App\Entity\Restaurant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/restaurant')]
#[IsGranted('ROLE_ADMIN')]
class AdminRestaurantController
{
    /**
     * Retourne les informations du restaurant.
     */
    #[Route('', name: 'app_admin_restaurant', methods: ['GET'])]
    public function index(
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Récupère le restaurant.
        // L'application ne possède actuellement qu'un seul restaurant.
        $restaurant = $entityManager
            ->getRepository(Restaurant::class)
            ->findOneBy([]);

        // Vérifie qu'un restaurant existe en base de données.
        if ($restaurant === null) {
            return new JsonResponse([
                'message' => 'Restaurant introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Retourne les informations utiles à l'administration.
        return new JsonResponse([
            'uuid' => $restaurant->getUuid(),
            'name' => $restaurant->getName(),
            'description' => $restaurant->getDescription(),
            'lunchOpeningTime' => $restaurant
                ->getLunchOpeningTime()
                ?->format('H:i'),
            'dinnerOpeningTime' => $restaurant
                ->getDinnerOpeningTime()
                ?->format('H:i'),
            'maxGuest' => $restaurant->getMaxGuest(),
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Modifie les informations administrables du restaurant.
     */
    #[Route('', name: 'app_admin_restaurant_update', methods: ['PATCH'])]
    public function update(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Récupère le restaurant.
        $restaurant = $entityManager
            ->getRepository(Restaurant::class)
            ->findOneBy([]);

        // Vérifie qu'un restaurant existe en base de données.
        if ($restaurant === null) {
            return new JsonResponse([
                'message' => 'Restaurant introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupère les données JSON envoyées par le frontend.
        $data = json_decode($request->getContent(), true);

        // Vérifie que les données reçues sont bien au format attendu.
        if (!is_array($data)) {
            return new JsonResponse([
                'message' => 'Le corps de la requête doit être un JSON valide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        /*
         * Vérifie que seuls les champs autorisés sont envoyés.
         */
        $allowedFields = [
            'lunchOpeningTime',
            'dinnerOpeningTime',
            'maxGuest',
        ];

        foreach (array_keys($data) as $field) {
            if (!in_array($field, $allowedFields, true)) {
                return new JsonResponse([
                    'message' => sprintf(
                        'Le champ "%s" n\'est pas autorisé.',
                        $field
                    ),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Vérifie qu'au moins un champ doit être modifié.
        if ($data === []) {
            return new JsonResponse([
                'message' => 'Aucune donnée à modifier.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        /*
         * Heure d'ouverture du service du midi.
         * Si le champ n'est pas envoyé, on conserve la valeur actuelle.
         */
        if (array_key_exists('lunchOpeningTime', $data)) {
            if (
                !is_string($data['lunchOpeningTime'])
                || !preg_match(
                    '/^\d{2}:\d{2}$/',
                    $data['lunchOpeningTime']
                )
            ) {
                return new JsonResponse([
                    'message' => 'L\'heure du midi doit respecter le format HH:MM.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $lunchOpeningTime = \DateTime::createFromFormat(
                'H:i',
                $data['lunchOpeningTime']
            );

            if (
                $lunchOpeningTime === false
                || $lunchOpeningTime->format('H:i')
                    !== $data['lunchOpeningTime']
            ) {
                return new JsonResponse([
                    'message' => 'L\'heure d\'ouverture du midi est invalide.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $restaurant->setLunchOpeningTime($lunchOpeningTime);
        }

        /*
         * Heure d'ouverture du service du soir.
         * Si le champ n'est pas envoyé, on conserve la valeur actuelle.
         */
        if (array_key_exists('dinnerOpeningTime', $data)) {
            if (
                !is_string($data['dinnerOpeningTime'])
                || !preg_match(
                    '/^\d{2}:\d{2}$/',
                    $data['dinnerOpeningTime']
                )
            ) {
                return new JsonResponse([
                    'message' => 'L\'heure du soir doit respecter le format HH:MM.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $dinnerOpeningTime = \DateTime::createFromFormat(
                'H:i',
                $data['dinnerOpeningTime']
            );

            if (
                $dinnerOpeningTime === false
                || $dinnerOpeningTime->format('H:i')
                    !== $data['dinnerOpeningTime']
            ) {
                return new JsonResponse([
                    'message' => 'L\'heure d\'ouverture du soir est invalide.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $restaurant->setDinnerOpeningTime($dinnerOpeningTime);
        }

        /*
         * Capacité maximale du restaurant.
         * Si le champ n'est pas envoyé, on conserve la valeur actuelle.
         */
        if (array_key_exists('maxGuest', $data)) {
            if (
                !is_int($data['maxGuest'])
                || $data['maxGuest'] <= 0
            ) {
                return new JsonResponse([
                    'message' => 'Le nombre maximal de convives doit être un entier positif.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $restaurant->setMaxGuest($data['maxGuest']);
        }

        // Met à jour la date de modification.
        $restaurant->setUpdatedAt(
            new \DateTimeImmutable()
        );

        // Enregistre les modifications.
        $entityManager->flush();

        // Retourne les informations mises à jour.
        return new JsonResponse([
            'message' => 'Informations du restaurant modifiées avec succès.',
            'restaurant' => [
                'uuid' => $restaurant->getUuid(),
                'name' => $restaurant->getName(),
                'description' => $restaurant->getDescription(),
                'lunchOpeningTime' => $restaurant
                    ->getLunchOpeningTime()
                    ?->format('H:i'),
                'dinnerOpeningTime' => $restaurant
                    ->getDinnerOpeningTime()
                    ?->format('H:i'),
                'maxGuest' => $restaurant->getMaxGuest(),
            ],
        ], JsonResponse::HTTP_OK);
    }
}