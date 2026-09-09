<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Food;
use App\Entity\Restaurant;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/admin/foods')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(
    name: 'Administration - Plats',
    description: 'Gestion des plats de la carte du restaurant.'
)]
class AdminFoodController
{
    /**
     * Retourne tous les plats.
     */
    #[Route('', name: 'app_admin_foods', methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/foods',
        summary: 'Lister les plats',
        description: 'Retourne tous les plats de la carte, triés par ordre alphabétique.',
        security: [
            ['bearerAuth' => []],
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des plats.',
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
                                        nullable: true,
                                        example: '550e8400-e29b-41d4-a716-446655440000'
                                    ),
                                    new OA\Property(
                                        property: 'title',
                                        type: 'string',
                                        nullable: true,
                                        example: 'Entrées'
                                    ),
                                ]
                            ),
                            new OA\Property(
                                property: 'createdAt',
                                type: 'string',
                                nullable: true,
                                example: '2026-09-09 10:30:00'
                            ),
                            new OA\Property(
                                property: 'updatedAt',
                                type: 'string',
                                nullable: true,
                                example: '2026-09-09 11:00:00'
                            ),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Authentification requise.'
            ),
            new OA\Response(
                response: 403,
                description: 'Accès réservé aux administrateurs.'
            ),
        ]
    )]
    public function index(
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Récupère tous les plats.
        $foods = $entityManager
            ->getRepository(Food::class)
            ->findBy([], ['title' => 'ASC']);

        // Prépare les données destinées au frontend.
        $foodData = [];

        foreach ($foods as $food) {
            $foodData[] = [
                'uuid' => $food->getUuid(),
                'title' => $food->getTitle(),
                'description' => $food->getDescription(),
                'price' => $food->getPrice(),
                'category' => [
                    'uuid' => $food->getCategory()?->getUuid(),
                    'title' => $food->getCategory()?->getTitle(),
                ],
                'createdAt' => $food
                    ->getCreatedAt()
                    ?->format('Y-m-d H:i:s'),
                'updatedAt' => $food
                    ->getUpdatedAt()
                    ?->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse(
            $foodData,
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Crée un nouveau plat.
     */
    #[Route('', name: 'app_admin_foods_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/admin/foods',
        summary: 'Créer un plat',
        description: 'Crée un nouveau plat et l\'associe à une catégorie existante.',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    'title',
                    'description',
                    'price',
                    'categoryUuid',
                ],
                properties: [
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        maxLength: 150,
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
                        property: 'categoryUuid',
                        type: 'string',
                        format: 'uuid',
                        example: '550e8400-e29b-41d4-a716-446655440000'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Plat créé avec succès.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Plat créé avec succès.'
                        ),
                        new OA\Property(
                            property: 'food',
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
                                new OA\Property(
                                    property: 'createdAt',
                                    type: 'string',
                                    nullable: true,
                                    example: '2026-09-09 10:30:00'
                                ),
                                new OA\Property(
                                    property: 'updatedAt',
                                    type: 'string',
                                    nullable: true,
                                    example: null
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides.'
            ),
            new OA\Response(
                response: 401,
                description: 'Authentification requise.'
            ),
            new OA\Response(
                response: 403,
                description: 'Accès réservé aux administrateurs.'
            ),
            new OA\Response(
                response: 404,
                description: 'Catégorie ou restaurant introuvable.'
            ),
        ]
    )]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Récupère les données JSON envoyées par le frontend.
        $data = json_decode($request->getContent(), true);

        // Vérifie que le corps de la requête est un JSON valide.
        if (!is_array($data)) {
            return new JsonResponse([
                'message' => 'Le corps de la requête doit être un JSON valide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie que les champs obligatoires sont présents.
        $requiredFields = [
            'title',
            'description',
            'price',
            'categoryUuid',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                return new JsonResponse([
                    'message' => sprintf(
                        'Le champ "%s" est obligatoire.',
                        $field
                    ),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Vérifie que seuls les champs autorisés sont envoyés.
        foreach (array_keys($data) as $field) {
            if (!in_array($field, $requiredFields, true)) {
                return new JsonResponse([
                    'message' => sprintf(
                        'Le champ "%s" n\'est pas autorisé.',
                        $field
                    ),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Vérifie le titre.
        if (
            !is_string($data['title'])
            || trim($data['title']) === ''
        ) {
            return new JsonResponse([
                'message' => 'Le titre est obligatoire.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($data['title']) > 150) {
            return new JsonResponse([
                'message' => 'Le titre ne peut pas dépasser 150 caractères.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie la description.
        if (
            !is_string($data['description'])
            || trim($data['description']) === ''
        ) {
            return new JsonResponse([
                'message' => 'La description est obligatoire.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie le prix.
        if (
            !is_string($data['price'])
            && !is_int($data['price'])
            && !is_float($data['price'])
        ) {
            return new JsonResponse([
                'message' => 'Le prix doit être un nombre.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $price = (string) $data['price'];

        if (
            !preg_match('/^\d+(\.\d{1,2})?$/', $price)
            || (float) $price <= 0
        ) {
            return new JsonResponse([
                'message' => 'Le prix doit être un nombre positif avec au maximum deux décimales.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie que l'UUID de la catégorie est valide.
        if (
            !is_string($data['categoryUuid'])
            || !Uuid::isValid($data['categoryUuid'])
        ) {
            return new JsonResponse([
                'message' => 'L\'UUID de la catégorie est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupère la catégorie.
        $category = $entityManager
            ->getRepository(Category::class)
            ->findOneBy([
                'uuid' => $data['categoryUuid'],
            ]);

        // Vérifie que la catégorie existe.
        if ($category === null) {
            return new JsonResponse([
                'message' => 'Catégorie introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupère le restaurant.
        // L'application ne possède actuellement qu'un seul restaurant.
        $restaurant = $entityManager
            ->getRepository(Restaurant::class)
            ->findOneBy([]);

        // Vérifie qu'un restaurant existe.
        if ($restaurant === null) {
            return new JsonResponse([
                'message' => 'Restaurant introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Crée le plat.
        $food = new Food();

        // Génère automatiquement un UUID unique.
        $food->setUuid(
            Uuid::v4()->toRfc4122()
        );

        $food->setTitle(
            trim($data['title'])
        );

        $food->setDescription(
            trim($data['description'])
        );

        $food->setPrice(
            number_format((float) $price, 2, '.', '')
        );

        $food->setCategory($category);
        $food->setRestaurant($restaurant);

        $food->setCreatedAt(
            new \DateTimeImmutable()
        );

        // Enregistre le plat.
        $entityManager->persist($food);
        $entityManager->flush();

        // Retourne le plat créé.
        return new JsonResponse([
            'message' => 'Plat créé avec succès.',
            'food' => [
                'uuid' => $food->getUuid(),
                'title' => $food->getTitle(),
                'description' => $food->getDescription(),
                'price' => $food->getPrice(),
                'category' => [
                    'uuid' => $category->getUuid(),
                    'title' => $category->getTitle(),
                ],
                'createdAt' => $food
                    ->getCreatedAt()
                    ?->format('Y-m-d H:i:s'),
                'updatedAt' => $food
                    ->getUpdatedAt()
                    ?->format('Y-m-d H:i:s'),
            ],
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Modifie un plat.
     */
    #[Route('/{uuid}', name: 'app_admin_foods_update', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/admin/foods/{uuid}',
        summary: 'Modifier un plat',
        description: 'Modifie les informations d\'un plat existant. Tous les champs sont facultatifs.',
        security: [
            ['bearerAuth' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID du plat.',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                    format: 'uuid'
                ),
                example: '550e8400-e29b-41d4-a716-446655440000'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        maxLength: 150,
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
                        property: 'categoryUuid',
                        type: 'string',
                        format: 'uuid',
                        example: '550e8400-e29b-41d4-a716-446655440000'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Plat modifié avec succès.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Plat modifié avec succès.'
                        ),
                        new OA\Property(
                            property: 'food',
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
                                            nullable: true,
                                            example: '550e8400-e29b-41d4-a716-446655440000'
                                        ),
                                        new OA\Property(
                                            property: 'title',
                                            type: 'string',
                                            nullable: true,
                                            example: 'Entrées'
                                        ),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'createdAt',
                                    type: 'string',
                                    nullable: true,
                                    example: '2026-09-09 10:30:00'
                                ),
                                new OA\Property(
                                    property: 'updatedAt',
                                    type: 'string',
                                    nullable: true,
                                    example: '2026-09-09 11:00:00'
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'UUID ou données invalides.'
            ),
            new OA\Response(
                response: 401,
                description: 'Authentification requise.'
            ),
            new OA\Response(
                response: 403,
                description: 'Accès réservé aux administrateurs.'
            ),
            new OA\Response(
                response: 404,
                description: 'Plat ou catégorie introuvable.'
            ),
        ]
    )]
    public function update(
        string $uuid,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifie que l'UUID reçu est valide.
        if (!Uuid::isValid($uuid)) {
            return new JsonResponse([
                'message' => 'L\'UUID du plat est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupère le plat.
        $food = $entityManager
            ->getRepository(Food::class)
            ->findOneBy([
                'uuid' => $uuid,
            ]);

        // Vérifie que le plat existe.
        if ($food === null) {
            return new JsonResponse([
                'message' => 'Plat introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupère les données JSON.
        $data = json_decode($request->getContent(), true);

        // Vérifie que le JSON est valide.
        if (!is_array($data)) {
            return new JsonResponse([
                'message' => 'Le corps de la requête doit être un JSON valide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie que le corps n'est pas vide.
        if ($data === []) {
            return new JsonResponse([
                'message' => 'Aucune donnée à modifier.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Liste des champs pouvant être modifiés.
        $allowedFields = [
            'title',
            'description',
            'price',
            'categoryUuid',
        ];

        // Vérifie que seuls les champs autorisés sont envoyés.
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

        // Modifie le titre si le champ est présent.
        if (array_key_exists('title', $data)) {
            if (
                !is_string($data['title'])
                || trim($data['title']) === ''
            ) {
                return new JsonResponse([
                    'message' => 'Le titre est obligatoire.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            if (mb_strlen($data['title']) > 150) {
                return new JsonResponse([
                    'message' => 'Le titre ne peut pas dépasser 150 caractères.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $food->setTitle(
                trim($data['title'])
            );
        }

        // Modifie la description si le champ est présent.
        if (array_key_exists('description', $data)) {
            if (
                !is_string($data['description'])
                || trim($data['description']) === ''
            ) {
                return new JsonResponse([
                    'message' => 'La description est obligatoire.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $food->setDescription(
                trim($data['description'])
            );
        }

        // Modifie le prix si le champ est présent.
        if (array_key_exists('price', $data)) {
            if (
                !is_string($data['price'])
                && !is_int($data['price'])
                && !is_float($data['price'])
            ) {
                return new JsonResponse([
                    'message' => 'Le prix doit être un nombre.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $price = (string) $data['price'];

            if (
                !preg_match('/^\d+(\.\d{1,2})?$/', $price)
                || (float) $price <= 0
            ) {
                return new JsonResponse([
                    'message' => 'Le prix doit être un nombre positif avec au maximum deux décimales.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $food->setPrice(
                number_format((float) $price, 2, '.', '')
            );
        }

        // Modifie la catégorie si le champ est présent.
        if (array_key_exists('categoryUuid', $data)) {
            if (
                !is_string($data['categoryUuid'])
                || !Uuid::isValid($data['categoryUuid'])
            ) {
                return new JsonResponse([
                    'message' => 'L\'UUID de la catégorie est invalide.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Récupère la nouvelle catégorie.
            $category = $entityManager
                ->getRepository(Category::class)
                ->findOneBy([
                    'uuid' => $data['categoryUuid'],
                ]);

            // Vérifie que la catégorie existe.
            if ($category === null) {
                return new JsonResponse([
                    'message' => 'Catégorie introuvable.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $food->setCategory($category);
        }

        // Met à jour la date de modification.
        $food->setUpdatedAt(
            new \DateTimeImmutable()
        );

        // Enregistre les modifications.
        $entityManager->flush();

        // Récupère la catégorie actuelle du plat.
        $category = $food->getCategory();

        // Retourne les données mises à jour.
        return new JsonResponse([
            'message' => 'Plat modifié avec succès.',
            'food' => [
                'uuid' => $food->getUuid(),
                'title' => $food->getTitle(),
                'description' => $food->getDescription(),
                'price' => $food->getPrice(),
                'category' => [
                    'uuid' => $category?->getUuid(),
                    'title' => $category?->getTitle(),
                ],
                'createdAt' => $food
                    ->getCreatedAt()
                    ?->format('Y-m-d H:i:s'),
                'updatedAt' => $food
                    ->getUpdatedAt()
                    ?->format('Y-m-d H:i:s'),
            ],
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Supprime un plat.
     */
    #[Route('/{uuid}', name: 'app_admin_foods_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/admin/foods/{uuid}',
        summary: 'Supprimer un plat',
        description: 'Supprime définitivement un plat de la carte.',
        security: [
            ['bearerAuth' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID du plat.',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                    format: 'uuid'
                ),
                example: '550e8400-e29b-41d4-a716-446655440000'
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Plat supprimé avec succès.'
            ),
            new OA\Response(
                response: 400,
                description: 'UUID invalide.'
            ),
            new OA\Response(
                response: 401,
                description: 'Authentification requise.'
            ),
            new OA\Response(
                response: 403,
                description: 'Accès réservé aux administrateurs.'
            ),
            new OA\Response(
                response: 404,
                description: 'Plat introuvable.'
            ),
        ]
    )]
    public function delete(
        string $uuid,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifie que l'UUID reçu est valide.
        if (!Uuid::isValid($uuid)) {
            return new JsonResponse([
                'message' => 'L\'UUID du plat est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupère le plat.
        $food = $entityManager
            ->getRepository(Food::class)
            ->findOneBy([
                'uuid' => $uuid,
            ]);

        // Vérifie que le plat existe.
        if ($food === null) {
            return new JsonResponse([
                'message' => 'Plat introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Supprime le plat.
        $entityManager->remove($food);
        $entityManager->flush();

        // Retourne une réponse sans contenu.
        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}