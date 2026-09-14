<?php

namespace App\Controller;

use App\Repository\RestaurantRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class RestaurantController
{
    /**
     * Retourne les informations publiques du restaurant.
     */
    #[Route(
        '/api/restaurant',
        name: 'api_restaurant',
        methods: ['GET']
    )]
    public function show(
        RestaurantRepository $restaurantRepository
    ): JsonResponse {
        // Récupère le restaurant unique de l'application.
        $restaurant = $restaurantRepository->findOneBy([]);

        // Vérifie qu'un restaurant existe.
        if ($restaurant === null) {
            return new JsonResponse([
                'message' => 'Restaurant introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Retourne uniquement les informations nécessaires au frontend public.
        return new JsonResponse([
            'name' => $restaurant->getName(),
            'description' => $restaurant->getDescription(),
            'lunchOpeningTime' => $restaurant
                ->getLunchOpeningTime()
                ?->format('H:i'),
            'lunchClosingTime' => $restaurant
                ->getLunchClosingTime()
                ?->format('H:i'),
            'dinnerOpeningTime' => $restaurant
                ->getDinnerOpeningTime()
                ?->format('H:i'),
            'dinnerClosingTime' => $restaurant
                ->getDinnerClosingTime()
                ?->format('H:i'),
        ], JsonResponse::HTTP_OK);
    }
}