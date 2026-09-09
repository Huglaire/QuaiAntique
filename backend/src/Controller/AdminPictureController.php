<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Entity\Restaurant;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/api/admin/pictures')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(
    name: 'Administration - Galerie',
    description: 'Gestion des photos de la galerie du restaurant.'
)]
class AdminPictureController
{
    /**
     * Retourne toutes les photos de la galerie.
     */
    #[Route('', name: 'app_admin_pictures', methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/pictures',
        summary: 'Lister les photos de la galerie',
        description: 'Retourne toutes les photos de la galerie, triées de la plus récente à la plus ancienne.',
        security: [
            ['bearerAuth' => []],
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des photos de la galerie.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'id',
                                type: 'integer',
                                example: 1
                            ),
                            new OA\Property(
                                property: 'title',
                                type: 'string',
                                example: 'La salle du restaurant'
                            ),
                            new OA\Property(
                                property: 'slug',
                                type: 'string',
                                example: 'la-salle-du-restaurant'
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
        // Récupère toutes les photos.
        $pictures = $entityManager
            ->getRepository(Picture::class)
            ->findBy([], ['createdAt' => 'DESC']);

        // Prépare les données destinées au frontend.
        $pictureData = [];

        foreach ($pictures as $picture) {
            $pictureData[] = [
                'id' => $picture->getId(),
                'title' => $picture->getTitle(),
                'slug' => $picture->getSlug(),
                'createdAt' => $picture
                    ->getCreatedAt()
                    ?->format('Y-m-d H:i:s'),
                'updatedAt' => $picture
                    ->getUpdatedAt()
                    ?->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse(
            $pictureData,
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Ajoute une photo à la galerie.
     */
    #[Route('', name: 'app_admin_pictures_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/admin/pictures',
        summary: 'Ajouter une photo',
        description: 'Ajoute une photo à la galerie à partir de son titre et génère automatiquement un slug unique.',
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
                        maxLength: 150,
                        example: 'La salle du restaurant'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Photo ajoutée avec succès.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Photo ajoutée avec succès.'
                        ),
                        new OA\Property(
                            property: 'picture',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'id',
                                    type: 'integer',
                                    example: 1
                                ),
                                new OA\Property(
                                    property: 'title',
                                    type: 'string',
                                    example: 'La salle du restaurant'
                                ),
                                new OA\Property(
                                    property: 'slug',
                                    type: 'string',
                                    example: 'la-salle-du-restaurant'
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
                description: 'Restaurant introuvable.'
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
        if (mb_strlen($data['title']) > 150) {
            return new JsonResponse([
                'message' => 'Le titre ne peut pas dépasser 150 caractères.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie que seuls les champs autorisés sont envoyés.
        $allowedFields = [
            'title',
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

        // Nettoie le titre avant de l'enregistrer.
        $title = trim($data['title']);

        // Génère un slug unique à partir du titre.
        $slug = $this->generateUniqueSlug(
            $title,
            $entityManager
        );

        // Crée la nouvelle photo.
        $picture = new Picture();

        $picture->setTitle($title);
        $picture->setSlug($slug);
        $picture->setRestaurant($restaurant);
        $picture->setCreatedAt(new \DateTimeImmutable());

        // Enregistre la photo.
        $entityManager->persist($picture);
        $entityManager->flush();

        // Retourne la photo créée.
        return new JsonResponse([
            'message' => 'Photo ajoutée avec succès.',
            'picture' => [
                'id' => $picture->getId(),
                'title' => $picture->getTitle(),
                'slug' => $picture->getSlug(),
                'createdAt' => $picture
                    ->getCreatedAt()
                    ?->format('Y-m-d H:i:s'),
                'updatedAt' => $picture
                    ->getUpdatedAt()
                    ?->format('Y-m-d H:i:s'),
            ],
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Modifie une photo de la galerie.
     */
    #[Route('/{id}', name: 'app_admin_pictures_update', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/admin/pictures/{id}',
        summary: 'Modifier une photo',
        description: 'Modifie le titre d’une photo et régénère automatiquement son slug.',
        security: [
            ['bearerAuth' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Identifiant de la photo.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
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
                        example: 'La nouvelle salle du restaurant'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Photo modifiée avec succès.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Photo modifiée avec succès.'
                        ),
                        new OA\Property(
                            property: 'picture',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'id',
                                    type: 'integer',
                                    example: 1
                                ),
                                new OA\Property(
                                    property: 'title',
                                    type: 'string',
                                    example: 'La nouvelle salle du restaurant'
                                ),
                                new OA\Property(
                                    property: 'slug',
                                    type: 'string',
                                    example: 'la-nouvelle-salle-du-restaurant'
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
                description: 'Photo introuvable.'
            ),
        ]
    )]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Récupère la photo.
        $picture = $entityManager
            ->getRepository(Picture::class)
            ->find($id);

        // Vérifie que la photo existe.
        if ($picture === null) {
            return new JsonResponse([
                'message' => 'Photo introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupère les données JSON envoyées par le frontend.
        $data = json_decode($request->getContent(), true);

        // Vérifie que le corps de la requête est un JSON valide.
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
        $allowedFields = [
            'title',
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

            // Nettoie le titre avant de l'enregistrer.
            $title = trim($data['title']);

            // Génère un nouveau slug unique à partir du nouveau titre.
            $slug = $this->generateUniqueSlug(
                $title,
                $entityManager,
                $picture
            );

            $picture->setTitle($title);
            $picture->setSlug($slug);
        }

        // Met à jour la date de modification.
        $picture->setUpdatedAt(new \DateTimeImmutable());

        // Enregistre les modifications.
        $entityManager->flush();

        // Retourne les données mises à jour.
        return new JsonResponse([
            'message' => 'Photo modifiée avec succès.',
            'picture' => [
                'id' => $picture->getId(),
                'title' => $picture->getTitle(),
                'slug' => $picture->getSlug(),
                'createdAt' => $picture
                    ->getCreatedAt()
                    ?->format('Y-m-d H:i:s'),
                'updatedAt' => $picture
                    ->getUpdatedAt()
                    ?->format('Y-m-d H:i:s'),
            ],
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Supprime une photo de la galerie.
     */
    #[Route('/{id}', name: 'app_admin_pictures_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/admin/pictures/{id}',
        summary: 'Supprimer une photo',
        description: 'Supprime définitivement une photo de la galerie.',
        security: [
            ['bearerAuth' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Identifiant de la photo.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Photo supprimée avec succès.'
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
                description: 'Photo introuvable.'
            ),
        ]
    )]
    public function delete(
        int $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Récupère la photo.
        $picture = $entityManager
            ->getRepository(Picture::class)
            ->find($id);

        // Vérifie que la photo existe.
        if ($picture === null) {
            return new JsonResponse([
                'message' => 'Photo introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Supprime la photo.
        $entityManager->remove($picture);
        $entityManager->flush();

        // Retourne une réponse sans contenu.
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Génère un slug unique à partir d'un titre.
     */
    private function generateUniqueSlug(
        string $title,
        EntityManagerInterface $entityManager,
        ?Picture $currentPicture = null
    ): string {
        // Utilise le générateur de slug fourni par Symfony.
        $slugger = new AsciiSlugger();

        // Transforme le titre en slug propre.
        $slug = $slugger
            ->slug($title)
            ->lower()
            ->toString();

        // Vérifie qu'un slug a bien pu être généré.
        if ($slug === '') {
            $slug = 'photo';
        }

        // Conserve le slug de base avant d'ajouter un éventuel suffixe.
        $baseSlug = $slug;

        // Commence la numérotation des doublons à 2.
        $counter = 2;

        // Récupère le repository des photos.
        $pictureRepository = $entityManager
            ->getRepository(Picture::class);

        // Cherche un slug disponible.
        while (true) {
            $existingPicture = $pictureRepository->findOneBy([
                'slug' => $slug,
            ]);

            // Aucun doublon : le slug est disponible.
            if (
                $existingPicture === null
                || (
                    $currentPicture !== null
                    && $existingPicture->getId() === $currentPicture->getId()
                )
            ) {
                return $slug;
            }

            // Le slug existe déjà : ajoute un numéro.
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }
}