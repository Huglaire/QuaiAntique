<?php

namespace App\DataFixtures;

use App\Entity\Menu;
use App\Repository\FoodRepository;
use App\Repository\RestaurantRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class MenuFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private FoodRepository $foodRepository,
        private RestaurantRepository $restaurantRepository
    ) {
    }

    public function getDependencies(): array
    {
        return [
            FoodFixtures::class,
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

        // Récupère les plats du menu du Quai.
        $tataki = $this->foodRepository->findOneBy([
            'title' => 'Tataki de saumon',
        ]);

        $filet = $this->foodRepository->findOneBy([
            'title' => 'Filet de bœuf',
        ]);

        $fondant = $this->foodRepository->findOneBy([
            'title' => 'Fondant au chocolat',
        ]);

        // Récupère les plats du menu Gourmand.
        $saladeChevre = $this->foodRepository->findOneBy([
            'title' => 'Salade de chèvre chaud',
        ]);

        $paveSaumon = $this->foodRepository->findOneBy([
            'title' => 'Pavé de saumon',
        ]);

        $tartePommes = $this->foodRepository->findOneBy([
            'title' => 'Tarte aux pommes',
        ]);

        if (
            $tataki === null
            || $filet === null
            || $fondant === null
            || $saladeChevre === null
            || $paveSaumon === null
            || $tartePommes === null
        ) {
            throw new \RuntimeException(
                'Un ou plusieurs plats sont introuvables.'
            );
        }

        /*
         * Menu du Quai
         *
         * Entrée + plat + dessert.
         */
        $menuQuai = new Menu();

        $menuQuai->setUuid(
            Uuid::v4()->toRfc4122()
        );

        $menuQuai->setTitle('Menu du Quai');

        $menuQuai->setDescription(
            'Une formule complète composée d’une entrée, d’un plat et d’un dessert.'
        );

        $menuQuai->setPrice('45.00');

        $menuQuai->setRestaurant($restaurant);

        $menuQuai->addFood($tataki);
        $menuQuai->addFood($filet);
        $menuQuai->addFood($fondant);

        $menuQuai->setCreatedAt(
            new \DateTimeImmutable()
        );

        $manager->persist($menuQuai);

        /*
         * Menu Gourmand
         *
         * Une formule différente avec une autre sélection
         * de plats du restaurant.
         */
        $menuGourmand = new Menu();

        $menuGourmand->setUuid(
            Uuid::v4()->toRfc4122()
        );

        $menuGourmand->setTitle('Menu Gourmand');

        $menuGourmand->setDescription(
            'Une formule généreuse avec une sélection de plats du restaurant.'
        );

        $menuGourmand->setPrice('42.00');

        $menuGourmand->setRestaurant($restaurant);

        $menuGourmand->addFood($saladeChevre);
        $menuGourmand->addFood($paveSaumon);
        $menuGourmand->addFood($tartePommes);

        $menuGourmand->setCreatedAt(
            new \DateTimeImmutable()
        );

        $manager->persist($menuGourmand);

        // Enregistre les menus et leurs associations avec les plats.
        $manager->flush();
    }
}