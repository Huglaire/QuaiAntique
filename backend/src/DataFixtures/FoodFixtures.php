<?php

namespace App\DataFixtures;

use App\Entity\Food;
use App\Repository\CategoryRepository;
use App\Repository\RestaurantRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class FoodFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private RestaurantRepository $restaurantRepository
    ) {
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            RestaurantFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Récupère le restaurant Quai Antique.
        $restaurant = $this->restaurantRepository->findOneBy([
            'name' => 'Quai Antique',
        ]);

        if ($restaurant === null) {
            throw new \RuntimeException(
                'Le restaurant Quai Antique est introuvable.'
            );
        }

        // Récupère les catégories.
        $entrees = $this->categoryRepository->findOneBy([
            'title' => 'Entrées',
        ]);

        $plats = $this->categoryRepository->findOneBy([
            'title' => 'Plats',
        ]);

        $desserts = $this->categoryRepository->findOneBy([
            'title' => 'Desserts',
        ]);

        if (
            $entrees === null
            || $plats === null
            || $desserts === null
        ) {
            throw new \RuntimeException(
                'Une ou plusieurs catégories sont introuvables.'
            );
        }

        // Liste des plats de test.
        $foods = [
            [
                'title' => 'Tataki de saumon',
                'description' => 'Emincés de saumon et sa sauce à l\'huile de sésame.',
                'price' => '12.00',
                'category' => $entrees,
            ],
            [
                'title' => 'Filet de bœuf',
                'description' => 'Filet de bœuf accompagné de légumes de saison et jus corsé.',
                'price' => '28.00',
                'category' => $plats,
            ],
            [
                'title' => 'Fondant au chocolat',
                'description' => 'Fondant au chocolat noir accompagné d’une glace vanille.',
                'price' => '10.00',
                'category' => $desserts,
            ],
        ];

        foreach ($foods as $foodData) {
            // Crée un nouveau plat.
            $food = new Food();

            // Génère automatiquement un UUID unique.
            $food->setUuid(
                Uuid::v4()->toRfc4122()
            );

            // Définit les informations du plat.
            $food->setTitle($foodData['title']);
            $food->setDescription($foodData['description']);
            $food->setPrice($foodData['price']);

            // Associe le plat à sa catégorie.
            $food->setCategory($foodData['category']);

            // Associe le plat au restaurant.
            $food->setRestaurant($restaurant);

            // Définit la date de création.
            $food->setCreatedAt(
                new \DateTimeImmutable()
            );

            // Prépare le plat pour l'enregistrement.
            $manager->persist($food);
        }

        // Enregistre tous les plats en base.
        $manager->flush();
    }
}