<?php

namespace App\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(
    name: 'Administration - Catégories',
    description: 'Gestion des catégories de la carte du restaurant.'
)]
class AdminCategoryController
{
    /**
     * Retourne toutes les catégories.
     */
    #[Route('', name: 'app_admin_categories', methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/categories',
        summary: 'Lister les catégories',
        description: 'Retourne toutes les catégories de la carte, triées par ordre alphabétique.',
        security: [
            ['bearerAuth' => []],
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des catégories.',
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
                                example: 'Entrées'
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
        // Récupère toutes les catégories.
        $categories = $entityManager
            ->getRepository(Category::class)
            ->findBy([], ['title' => 'ASC']);

        // Prépare les données destinées au frontend.
        $categoryData = [];

        foreach ($categories as $category) {
            $categoryData[] = [
                'uuid' => $category->getUuid(),
                'title' => $category->getTitle(),
                'createdAt' => $category
                    ->getCreatedAt()
                    ?->format('Y-m-d H:i:s'),
                'updatedAt' => $category
                    ->getUpdatedAt()
                    ?->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse(
            $categoryData,
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Crée une nouvelle catégorie.
     */
    #[Route('', name: 'app_admin_categories_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/admin/categories',
        summary: 'Créer une catégorie',
        description: 'Crée une nouvelle catégorie de la carte.',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        maxLength: 100,
                        example: 'Entrées'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Catégorie créée avec succès.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Catégorie créée avec succès.'
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
                response: 409,
                description: 'Une catégorie portant déjà ce nom existe.'
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

        // Vérifie que le champ title est présent.
        if (!array_key_exists('title', $data)) {
            return new JsonResponse([
                'message' => 'Le champ "title" est obligatoire.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie que le titre est une chaîne non vide.
        if (
            !is_string($data['title'])
            || trim($data['title']) === ''
        ) {
            return new JsonResponse([
                'message' => 'Le titre est obligatoire.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie la longueur maximale du titre.
        if (mb_strlen($data['title']) > 100) {
            return new JsonResponse([
                'message' => 'Le titre ne peut pas dépasser 100 caractères.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie que seuls les champs autorisés sont envoyés.
        foreach (array_keys($data) as $field) {
            if ($field !== 'title') {
                return new JsonResponse([
                    'message' => sprintf(
                        'Le champ "%s" n\'est pas autorisé.',
                        $field
                    ),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Nettoie le titre avant de l'enregistrer.
        $title = trim($data['title']);

        // Vérifie qu'une catégorie portant déjà ce nom n'existe pas.
        $existingCategory = $entityManager
            ->getRepository(Category::class)
            ->findOneBy([
                'title' => $title,
            ]);

        if ($existingCategory !== null) {
            return new JsonResponse([
                'message' => 'Une catégorie portant ce nom existe déjà.',
            ], JsonResponse::HTTP_CONFLICT);
        }

        // Crée la catégorie.
        $category = new Category();

        // Génère automatiquement un UUID unique.
        $category->setUuid(
            Uuid::v4()->toRfc4122()
        );

        $category->setTitle($title);

        $category->setCreatedAt(
            new \DateTimeImmutable()
        );

        // Enregistre la catégorie.
        $entityManager->persist($category);
        $entityManager->flush();

        // Retourne la catégorie créée.
        return new JsonResponse([
            'message' => 'Catégorie créée avec succès.',
            'category' => [
                'uuid' => $category->getUuid(),
                'title' => $category->getTitle(),
                'createdAt' => $category
                    ->getCreatedAt()
                    ?->format('Y-m-d H:i:s'),
                'updatedAt' => $category
                    ->getUpdatedAt()
                    ?->format('Y-m-d H:i:s'),
            ],
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Modifie une catégorie.
     */
    #[Route('/{uuid}', name: 'app_admin_categories_update', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/admin/categories/{uuid}',
        summary: 'Modifier une catégorie',
        description: 'Modifie le titre d’une catégorie existante.',
        security: [
            ['bearerAuth' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID de la catégorie.',
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
                        maxLength: 100,
                        example: 'Entrées'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Catégorie modifiée avec succès.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Catégorie modifiée avec succès.'
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
                description: 'Catégorie introuvable.'
            ),
            new OA\Response(
                response: 409,
                description: 'Une autre catégorie portant déjà ce nom existe.'
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
                'message' => 'L\'UUID de la catégorie est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupère la catégorie.
        $category = $entityManager
            ->getRepository(Category::class)
            ->findOneBy([
                'uuid' => $uuid,
            ]);

        // Vérifie que la catégorie existe.
        if ($category === null) {
            return new JsonResponse([
                'message' => 'Catégorie introuvable.',
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

        // Vérifie que seuls les champs autorisés sont envoyés.
        foreach (array_keys($data) as $field) {
            if ($field !== 'title') {
                return new JsonResponse([
                    'message' => sprintf(
                        'Le champ "%s" n\'est pas autorisé.',
                        $field
                    ),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Modifie le titre.
        if (array_key_exists('title', $data)) {
            // Vérifie que le titre est une chaîne non vide.
            if (
                !is_string($data['title'])
                || trim($data['title']) === ''
            ) {
                return new JsonResponse([
                    'message' => 'Le titre est obligatoire.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Vérifie la longueur maximale du titre.
            if (mb_strlen($data['title']) > 100) {
                return new JsonResponse([
                    'message' => 'Le titre ne peut pas dépasser 100 caractères.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Nettoie le titre.
            $title = trim($data['title']);

            // Vérifie qu'une autre catégorie ne possède pas déjà ce nom.
            $existingCategory = $entityManager
                ->getRepository(Category::class)
                ->findOneBy([
                    'title' => $title,
                ]);

            if (
                $existingCategory !== null
                && $existingCategory->getId() !== $category->getId()
            ) {
                return new JsonResponse([
                    'message' => 'Une catégorie portant ce nom existe déjà.',
                ], JsonResponse::HTTP_CONFLICT);
            }

            // Met à jour le titre.
            $category->setTitle($title);
        }

        // Met à jour la date de modification.
        $category->setUpdatedAt(
            new \DateTimeImmutable()
        );

        // Enregistre les modifications.
        $entityManager->flush();

        // Retourne les données mises à jour.
        return new JsonResponse([
            'message' => 'Catégorie modifiée avec succès.',
            'category' => [
                'uuid' => $category->getUuid(),
                'title' => $category->getTitle(),
                'createdAt' => $category
                    ->getCreatedAt()
                    ?->format('Y-m-d H:i:s'),
                'updatedAt' => $category
                    ->getUpdatedAt()
                    ?->format('Y-m-d H:i:s'),
            ],
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Supprime une catégorie.
     */
    #[Route('/{uuid}', name: 'app_admin_categories_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/admin/categories/{uuid}',
        summary: 'Supprimer une catégorie',
        description: 'Supprime une catégorie uniquement si elle ne contient aucun plat.',
        security: [
            ['bearerAuth' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID de la catégorie.',
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
                description: 'Catégorie supprimée avec succès.'
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
                description: 'Catégorie introuvable.'
            ),
            new OA\Response(
                response: 409,
                description: 'La catégorie contient encore des plats et ne peut pas être supprimée.'
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
                'message' => 'L\'UUID de la catégorie est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupère la catégorie.
        $category = $entityManager
            ->getRepository(Category::class)
            ->findOneBy([
                'uuid' => $uuid,
            ]);

        // Vérifie que la catégorie existe.
        if ($category === null) {
            return new JsonResponse([
                'message' => 'Catégorie introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifie si la catégorie contient encore des plats.
        if ($category->getFoods()->count() > 0) {
            return new JsonResponse([
                'message' => 'Impossible de supprimer une catégorie contenant des plats.',
            ], JsonResponse::HTTP_CONFLICT);
        }

        // Supprime la catégorie.
        $entityManager->remove($category);
        $entityManager->flush();

        // Retourne une réponse sans contenu.
        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}