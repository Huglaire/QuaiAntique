<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/menus')]
#[OA\Tag(
    name: 'Carte - Menus',
    description: 'Consultation des menus disponibles à la carte.'
)]
class MenuController extends AbstractController
{
    /**
     * Affiche les menus disponibles sur la carte.
     */
    #[Route('', name: 'api_menus', methods: ['GET'])]
    #[OA\Get(
        path: '/api/menus',
        summary: 'Afficher les menus disponibles',
        description: 'Retourne tous les menus disponibles à la carte, avec les plats associés, triés par ordre alphabétique.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des menus disponibles.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'uuid',
                                type: 'string',
                                format: 'uuid',
                                example: '550e8400-e29b-41d4-a716-446655440000'
                            ),
                            new OA\Property(
                                property: 'title',
                                type: 'string',
                                example: 'Menu du Quai'
                            ),
                            new OA\Property(
                                property: 'description',
                                type: 'string',
                                example: 'Un menu complet composé d\'une entrée, d\'un plat et d\'un dessert.'
                            ),
                            new OA\Property(
                                property: 'price',
                                type: 'string',
                                example: '45.00'
                            ),
                            new OA\Property(
                                property: 'foods',
                                type: 'array',
                                description: 'Liste des plats proposés dans le menu.',
                                items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(
                                            property: 'uuid',
                                            type: 'string',
                                            format: 'uuid',
                                            example: '550e8400-e29b-41d4-a716-446655440000'
                                        ),
                                        new OA\Property(
                                            property: 'title',
                                            type: 'string',
                                            example: 'Filet de bœuf'
                                        ),
                                        new OA\Property(
                                            property: 'price',
                                            type: 'string',
                                            example: '28.00'
                                        ),
                                    ]
                                )
                            ),
                        ]
                    )
                )
            ),
        ]
    )]
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