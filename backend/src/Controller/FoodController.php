<?php

namespace App\Controller;

use App\Repository\FoodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/foods')]
class FoodController extends AbstractController
{
    /**
     * Affiche les plats à la carte.
     */
    #[Route('', name: 'api_foods', methods: ['GET'])]
    public function index(FoodRepository $foodRepository): JsonResponse
    {
        // Récupère tous les plats et les trie par catégorie puis par titre.
        $foods = $foodRepository->findBy([], [
            'title' => 'ASC',
        ]);

        $data = [];

        foreach ($foods as $food) {
            $category = $food->getCategory();

            $data[] = [
                'uuid' => $food->getUuid(),
                'title' => $food->getTitle(),
                'description' => $food->getDescription(),
                'price' => $food->getPrice(),
                'category' => $category !== null
                    ? [
                        'uuid' => $category->getUuid(),
                        'title' => $category->getTitle(),
                    ]
                    : null,
            ];
        }

        return $this->json($data);
    }
}