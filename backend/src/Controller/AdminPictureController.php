<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Entity\Restaurant;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

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
                                property: 'imageName',
                                type: 'string',
                                example: 'a8f2c1e4b6d7.jpg'
                            ),
                            new OA\Property(
                                property: 'imageUrl',
                                type: 'string',
                                example: '/uploads/gallery/a8f2c1e4b6d7.jpg'
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
            $pictureData[] = $this->formatPicture($picture);
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
        description: 'Ajoute une photo à la galerie avec son titre et son fichier image.',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['title', 'image'],
                    properties: [
                        new OA\Property(
                            property: 'title',
                            type: 'string',
                            maxLength: 150,
                            example: 'La salle du restaurant'
                        ),
                        new OA\Property(
                            property: 'image',
                            type: 'string',
                            format: 'binary'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Photo ajoutée avec succès.'
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
            new OA\Response(
                response: 500,
                description: 'Erreur lors de l’enregistrement du fichier.'
            ),
        ]
    )]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): JsonResponse {
        // Récupère le titre envoyé dans le formulaire.
        $title = $request->request->get('title');

        // Vérifie que le titre est une chaîne non vide.
        if (
            !is_string($title)
            || trim($title) === ''
        ) {
            return new JsonResponse([
                'message' => 'Le titre est obligatoire.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie la longueur maximale du titre.
        if (mb_strlen($title) > 150) {
            return new JsonResponse([
                'message' => 'Le titre ne peut pas dépasser 150 caractères.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupère le fichier envoyé.
        $image = $request->files->get('image');

        // Vérifie qu'un fichier a bien été envoyé.
        if (!$image instanceof UploadedFile) {
            return new JsonResponse([
                'message' => 'Le fichier image est obligatoire.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie que le fichier a été correctement envoyé.
        if (!$image->isValid()) {
            return new JsonResponse([
                'message' => 'Le fichier image est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie la taille maximale du fichier.
        if ($image->getSize() > 5 * 1024 * 1024) {
            return new JsonResponse([
                'message' => 'L\'image ne peut pas dépasser 5 Mo.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie le type MIME réel du fichier.
        $mimeType = $image->getMimeType();

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            return new JsonResponse([
                'message' => 'Le format de l\'image doit être JPEG, PNG ou WebP.',
            ], JsonResponse::HTTP_BAD_REQUEST);
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
        $title = trim($title);

        // Génère un slug unique à partir du titre.
        $slug = $this->generateUniqueSlug(
            $title,
            $entityManager,
            $slugger
        );

        // Détermine l'extension à utiliser à partir du type MIME.
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        };

        // Génère un nom de fichier aléatoire et sécurisé.
        $imageName = bin2hex(random_bytes(16)) . '.' . $extension;

        // Définit le dossier de stockage des images.
        $uploadDirectory = dirname(__DIR__, 2)
            . '/public/uploads/gallery';

        // Crée le dossier s'il n'existe pas encore.
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0775, true);
        }

        try {
            // Déplace le fichier dans le dossier de la galerie.
            $image->move(
                $uploadDirectory,
                $imageName
            );
        } catch (FileException $exception) {
            return new JsonResponse([
                'message' => 'Impossible d\'enregistrer le fichier image.',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Crée la nouvelle photo.
        $picture = new Picture();

        $picture->setTitle($title);
        $picture->setSlug($slug);
        $picture->setImageName($imageName);
        $picture->setRestaurant($restaurant);
        $picture->setCreatedAt(new \DateTimeImmutable());

        // Enregistre la photo.
        $entityManager->persist($picture);
        $entityManager->flush();

        // Retourne la photo créée.
        return new JsonResponse([
            'message' => 'Photo ajoutée avec succès.',
            'picture' => $this->formatPicture($picture),
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
                description: 'Photo modifiée avec succès.'
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
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
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
                $slugger,
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
            'picture' => $this->formatPicture($picture),
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Supprime une photo de la galerie.
     */
    #[Route('/{id}', name: 'app_admin_pictures_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/admin/pictures/{id}',
        summary: 'Supprimer une photo',
        description: 'Supprime définitivement une photo de la galerie ainsi que son fichier image.',
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

        // Récupère le nom du fichier avant de supprimer l'entité.
        $imageName = $picture->getImageName();

        // Supprime la photo de la base de données.
        $entityManager->remove($picture);
        $entityManager->flush();

        // Supprime le fichier image s'il existe.
        $imagePath = dirname(__DIR__, 2)
            . '/public/uploads/gallery/'
            . $imageName;

        if (
            $imageName !== null
            && is_file($imagePath)
        ) {
            unlink($imagePath);
        }

        // Retourne une réponse sans contenu.
        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }

    /**
     * Génère un slug unique à partir d'un titre.
     */
    private function generateUniqueSlug(
        string $title,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ?Picture $currentPicture = null
    ): string {
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

            // Aucun doublon ou photo actuelle : le slug est disponible.
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

    /**
     * Formate une photo pour les réponses JSON.
     */
    private function formatPicture(
        Picture $picture
    ): array {
        return [
            'id' => $picture->getId(),
            'title' => $picture->getTitle(),
            'slug' => $picture->getSlug(),
            'imageName' => $picture->getImageName(),
            'imageUrl' => '/uploads/gallery/' . $picture->getImageName(),
            'createdAt' => $picture
                ->getCreatedAt()
                ?->format('Y-m-d H:i:s'),
            'updatedAt' => $picture
                ->getUpdatedAt()
                ?->format('Y-m-d H:i:s'),
        ];
    }
}