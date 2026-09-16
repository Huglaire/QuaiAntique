<?php

namespace App\DataFixtures;

use App\Entity\Restaurant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class RestaurantFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Crée le restaurant Quai Antique.
        $restaurant = new Restaurant();

        // Génère automatiquement un UUID unique.
        $restaurant->setUuid(
            Uuid::v4()->toRfc4122()
        );

        // Informations générales du restaurant.
        $restaurant->setName('Quai Antique');

        $restaurant->setDescription(
            'Restaurant gastronomique du chef Arnaud Michant. Tradition et raffinement sont les maîtres-mots pour satisfaire nos clients'
        );

        // Définit les horaires du service du midi.
        $restaurant->setLunchOpeningTime(
            new \DateTime('12:00')
        );

        $restaurant->setLunchClosingTime(
            new \DateTime('13:00')
        );

        // Définit les horaires du service du soir.
        $restaurant->setDinnerOpeningTime(
            new \DateTime('19:00')
        );

        $restaurant->setDinnerClosingTime(
            new \DateTime('22:00')
        );

        // Définit la capacité maximale du restaurant.
        $restaurant->setMaxGuest(50);

        // Enregistre la date de création.
        $restaurant->setCreatedAt(
            new \DateTimeImmutable()
        );

        // Prépare le restaurant pour son enregistrement.
        $manager->persist($restaurant);

        // Enregistre le restaurant en base de données.
        $manager->flush();
    }
}