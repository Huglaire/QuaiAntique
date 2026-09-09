<?php

namespace App\Controller;

use App\Repository\FoodRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/foods')]
#[OA\Tag(
    name: 'Carte - Plats',
    description: 'Consultation des plats disponibles à la carte.'
)]
class FoodController extends AbstractController
{
    /**
     * Affiche les plats à la carte.
     */
    #[Route('', name: 'api_foods', methods: ['GET'])]
    #[OA\Get(
        path: '/api/foods',
        summary: 'Afficher les plats à la carte',
        description: 'Retourne tous les plats disponibles à la carte, triés par ordre alphabétique.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des plats disponibles à la carte.',
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
                                example: 'Tataki de saumon'
                            ),
                            new OA\Property(
                                property: 'description',
                                type: 'string',
                                example: 'Emincés de saumon et sa sauce à l\'huile de sésame.'
                            ),
                            new OA\Property(
                                property: 'price',
                                type: 'string',
                                example: '12.00'
                            ),
                            new OA\Property(
                                property: 'category',
                                type: 'object',
                                nullable: true,
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
                                        example: 'Entrées'
                                    ),
                                ]
                            ),
                        ]
                    )
                )
            ),
        ]
    )]
    public function index(FoodRepository $foodRepository): JsonResponse
    {
        // Récupère tous les plats et les trie par titre.
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