<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/menus')]
class MenuController extends AbstractController
{
    /**
     * Affiche les menus disponibles sur la carte.
     */
    #[Route('', name: 'api_menus', methods: ['GET'])]
    public function index(MenuRepository $menuRepository): JsonResponse
    {
        // Récupère tous les menus et les trie par titre.
        $menus = $menuRepository->findBy([], [
            'title' => 'ASC',
        ]);

        $data = [];

        foreach ($menus as $menu) {
            $foods = [];

            // Récupère les plats associés à chaque menu.
            foreach ($menu->getFoods() as $food) {
                $foods[] = [
                    'uuid' => $food->getUuid(),
                    'title' => $food->getTitle(),
                    'price' => $food->getPrice(),
                ];
            }

            $data[] = [
                'uuid' => $menu->getUuid(),
                'title' => $menu->getTitle(),
                'description' => $menu->getDescription(),
                'price' => $menu->getPrice(),
                'foods' => $foods,
            ];
        }

        return $this->json($data);
    }
}