<?php

namespace App\Controller;

use App\Entity\Picture;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/pictures')]
#[OA\Tag(
    name: 'Galerie',
    description: 'Consultation publique de la galerie du restaurant.'
)]
class PictureController
{
    /**
     * Retourne toutes les photos de la galerie.
     */
    #[Route('', name: 'app_pictures', methods: ['GET'])]
    #[OA\Get(
        path: '/api/pictures',
        summary: 'Lister les photos de la galerie',
        description: 'Retourne toutes les photos de la galerie, triées de la plus récente à la plus ancienne.',
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
            )
        ]
    )]
    public function index(
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Récupère toutes les photos, de la plus récente à la plus ancienne.
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
}