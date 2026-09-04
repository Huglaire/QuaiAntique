<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Repository\FoodRepository;
use App\Repository\MenuRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/admin/menus')]
#[IsGranted('ROLE_ADMIN')]
class AdminMenuController extends AbstractController
{
    /**
     * Liste tous les menus.
     */
    #[Route('', name: 'app_admin_menus', methods: ['GET'])]
    public function index(MenuRepository $menuRepository): JsonResponse
    {
        $menus = $menuRepository->findBy([], [
            'title' => 'ASC',
        ]);

        $data = [];

        foreach ($menus as $menu) {
            $foods = [];

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
                'createdAt' => $menu->getCreatedAt()?->format(
                    \DateTimeInterface::ATOM
                ),
                'updatedAt' => $menu->getUpdatedAt()?->format(
                    \DateTimeInterface::ATOM
                ),
            ];
        }

        return $this->json($data);
    }

    /**
     * Crée un menu.
     */
    #[Route('', name: 'app_admin_menus_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        FoodRepository $foodRepository,
        RestaurantRepository $restaurantRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'message' => 'Le JSON envoyé est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie les champs autorisés.
        $allowedFields = [
            'title',
            'description',
            'price',
            'foodUuids',
        ];

        foreach (array_keys($data) as $field) {
            if (!in_array($field, $allowedFields, true)) {
                return $this->json([
                    'message' => sprintf(
                        'Le champ "%s" n\'est pas autorisé.',
                        $field
                    ),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Vérifie les champs obligatoires.
        foreach ([
            'title',
            'description',
            'price',
            'foodUuids',
        ] as $field) {
            if (!array_key_exists($field, $data)) {
                return $this->json([
                    'message' => sprintf(
                        'Le champ "%s" est obligatoire.',
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
            return $this->json([
                'message' => 'Le titre est obligatoire.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($data['title']) > 150) {
            return $this->json([
                'message' => 'Le titre ne peut pas dépasser 150 caractères.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie la description.
        if (
            !is_string($data['description'])
            || trim($data['description']) === ''
        ) {
            return $this->json([
                'message' => 'La description est obligatoire.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie le prix.
        if (
            !is_string($data['price'])
            && !is_int($data['price'])
            && !is_float($data['price'])
        ) {
            return $this->json([
                'message' => 'Le prix doit être un nombre.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $price = (string) $data['price'];

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $price)) {
            return $this->json([
                'message' => 'Le prix doit comporter au maximum deux décimales.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ((float) $price <= 0) {
            return $this->json([
                'message' => 'Le prix doit être supérieur à zéro.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $price = number_format(
            (float) $price,
            2,
            '.',
            ''
        );

        // Vérifie la liste des plats.
        if (!is_array($data['foodUuids'])) {
            return $this->json([
                'message' => 'foodUuids doit être un tableau.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (count($data['foodUuids']) === 0) {
            return $this->json([
                'message' => 'Le menu doit contenir au moins un plat.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie chaque UUID et récupère les plats correspondants.
        $foods = [];

        foreach ($data['foodUuids'] as $foodUuid) {
            if (
                !is_string($foodUuid)
                || !Uuid::isValid($foodUuid)
            ) {
                return $this->json([
                    'message' => 'Un ou plusieurs UUID de plats sont invalides.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $food = $foodRepository->findOneBy([
                'uuid' => $foodUuid,
            ]);

            if ($food === null) {
                return $this->json([
                    'message' => sprintf(
                        'Le plat "%s" est introuvable.',
                        $foodUuid
                    ),
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $foods[] = $food;
        }

        // Récupère le restaurant.
        $restaurant = $restaurantRepository->findOneBy([
            'name' => 'Quai Antique',
        ]);

        if ($restaurant === null) {
            return $this->json([
                'message' => 'Le restaurant est introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Crée le menu.
        $menu = new Menu();

        $menu->setUuid(
            Uuid::v4()->toRfc4122()
        );

        $menu->setTitle(
            trim($data['title'])
        );

        $menu->setDescription(
            trim($data['description'])
        );

        $menu->setPrice($price);

        $menu->setRestaurant($restaurant);

        $menu->setCreatedAt(
            new \DateTimeImmutable()
        );

        // Associe les plats au menu.
        foreach ($foods as $food) {
            $menu->addFood($food);
        }

        $entityManager->persist($menu);
        $entityManager->flush();

        return $this->json([
            'message' => 'Menu créé avec succès.',
            'menu' => $this->formatMenu($menu),
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Modifie un menu.
     */
    #[Route(
        '/{uuid}',
        name: 'app_admin_menus_update',
        methods: ['PATCH']
    )]
    public function update(
        string $uuid,
        Request $request,
        MenuRepository $menuRepository,
        FoodRepository $foodRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifie le format de l'UUID.
        if (!Uuid::isValid($uuid)) {
            return $this->json([
                'message' => 'UUID invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Recherche le menu.
        $menu = $menuRepository->findOneBy([
            'uuid' => $uuid,
        ]);

        if ($menu === null) {
            return $this->json([
                'message' => 'Menu introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'message' => 'Le JSON envoyé est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($data === []) {
            return $this->json([
                'message' => 'Aucune donnée à modifier.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie les champs autorisés.
        $allowedFields = [
            'title',
            'description',
            'price',
            'foodUuids',
        ];

        foreach (array_keys($data) as $field) {
            if (!in_array($field, $allowedFields, true)) {
                return $this->json([
                    'message' => sprintf(
                        'Le champ "%s" n\'est pas autorisé.',
                        $field
                    ),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Modification du titre.
        if (array_key_exists('title', $data)) {
            if (
                !is_string($data['title'])
                || trim($data['title']) === ''
            ) {
                return $this->json([
                    'message' => 'Le titre ne peut pas être vide.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            if (mb_strlen($data['title']) > 150) {
                return $this->json([
                    'message' => 'Le titre ne peut pas dépasser 150 caractères.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $menu->setTitle(
                trim($data['title'])
            );
        }

        // Modification de la description.
        if (array_key_exists('description', $data)) {
            if (
                !is_string($data['description'])
                || trim($data['description']) === ''
            ) {
                return $this->json([
                    'message' => 'La description ne peut pas être vide.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $menu->setDescription(
                trim($data['description'])
            );
        }

        // Modification du prix.
        if (array_key_exists('price', $data)) {
            if (
                !is_string($data['price'])
                && !is_int($data['price'])
                && !is_float($data['price'])
            ) {
                return $this->json([
                    'message' => 'Le prix doit être un nombre.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $price = (string) $data['price'];

            if (!preg_match('/^\d+(\.\d{1,2})?$/', $price)) {
                return $this->json([
                    'message' => 'Le prix doit comporter au maximum deux décimales.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            if ((float) $price <= 0) {
                return $this->json([
                    'message' => 'Le prix doit être supérieur à zéro.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $menu->setPrice(
                number_format(
                    (float) $price,
                    2,
                    '.',
                    ''
                )
            );
        }

        // Modification des plats associés.
        if (array_key_exists('foodUuids', $data)) {
            if (!is_array($data['foodUuids'])) {
                return $this->json([
                    'message' => 'foodUuids doit être un tableau.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            if (count($data['foodUuids']) === 0) {
                return $this->json([
                    'message' => 'Le menu doit contenir au moins un plat.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $newFoods = [];

            foreach ($data['foodUuids'] as $foodUuid) {
                if (
                    !is_string($foodUuid)
                    || !Uuid::isValid($foodUuid)
                ) {
                    return $this->json([
                        'message' => 'Un ou plusieurs UUID de plats sont invalides.',
                    ], JsonResponse::HTTP_BAD_REQUEST);
                }

                $food = $foodRepository->findOneBy([
                    'uuid' => $foodUuid,
                ]);

                if ($food === null) {
                    return $this->json([
                        'message' => sprintf(
                            'Le plat "%s" est introuvable.',
                            $foodUuid
                        ),
                    ], JsonResponse::HTTP_NOT_FOUND);
                }

                $newFoods[] = $food;
            }

            // Remplace les associations existantes.
            foreach ($menu->getFoods()->toArray() as $food) {
                $menu->removeFood($food);
            }

            foreach ($newFoods as $food) {
                $menu->addFood($food);
            }
        }

        $menu->setUpdatedAt(
            new \DateTimeImmutable()
        );

        $entityManager->flush();

        return $this->json([
            'message' => 'Menu modifié avec succès.',
            'menu' => $this->formatMenu($menu),
        ]);
    }

    /**
     * Supprime un menu.
     */
    #[Route(
        '/{uuid}',
        name: 'app_admin_menus_delete',
        methods: ['DELETE']
    )]
    public function delete(
        string $uuid,
        MenuRepository $menuRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifie le format de l'UUID.
        if (!Uuid::isValid($uuid)) {
            return $this->json([
                'message' => 'UUID invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Recherche le menu.
        $menu = $menuRepository->findOneBy([
            'uuid' => $uuid,
        ]);

        if ($menu === null) {
            return $this->json([
                'message' => 'Menu introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($menu);
        $entityManager->flush();

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }

    /**
     * Formate un menu pour les réponses JSON.
     */
    private function formatMenu(Menu $menu): array
    {
        $foods = [];

        foreach ($menu->getFoods() as $food) {
            $foods[] = [
                'uuid' => $food->getUuid(),
                'title' => $food->getTitle(),
                'price' => $food->getPrice(),
            ];
        }

        return [
            'uuid' => $menu->getUuid(),
            'title' => $menu->getTitle(),
            'description' => $menu->getDescription(),
            'price' => $menu->getPrice(),
            'foods' => $foods,
            'createdAt' => $menu->getCreatedAt()?->format(
                \DateTimeInterface::ATOM
            ),
            'updatedAt' => $menu->getUpdatedAt()?->format(
                \DateTimeInterface::ATOM
            ),
        ];
    }
}