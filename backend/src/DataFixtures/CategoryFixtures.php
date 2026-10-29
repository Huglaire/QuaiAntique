<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Liste des catégories du restaurant.
        $categories = [
            'Entrées',
            'Plats',
            'Desserts',
        ];

        foreach ($categories as $title) {
            // Crée une nouvelle catégorie.
            $category = new Category();

            // Génère automatiquement un UUID unique.
            $category->setUuid(
                Uuid::v4()->toRfc4122()
            );

            // Définit le titre de la catégorie.
            $category->setTitle($title);

            // Définit la date de création.
            $category->setCreatedAt(
                new \DateTimeImmutable()
            );

            // Prépare la catégorie pour l'enregistrement.
            $manager->persist($category);
        }

        // Enregistre toutes les catégories en base.
        $manager->flush();
    }
}