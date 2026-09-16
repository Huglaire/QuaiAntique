<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Crée l'utilisateur de test principal.
        $user = new User();

        // Génère automatiquement un UUID unique.
        $user->setUuid(
            Uuid::v4()->toRfc4122()
        );

        // Informations personnelles.
        $user->setFirstName('User');
        $user->setLastName('Test');

        // Adresse e-mail utilisée pour les tests.
        $user->setEmail('user@mail.fr');

        // Rôle de l'utilisateur.
        $user->setRoles(['ROLE_USER']);

        // Nombre de convives par défaut.
        $user->setGuestNumber(5);

        // Allergies renseignées par l'utilisateur.
        $user->setAllergy('Aucune');

        // Mot de passe en clair uniquement à cette étape.
        // Il sera automatiquement haché avant d'être enregistré.
        $user->setPassword(
            $this->passwordHasher->hashPassword(
                $user,
                'password'
            )
        );

        // Enregistre la date de création.
        $user->setCreatedAt(
            new \DateTimeImmutable()
        );

        // Prépare l'utilisateur pour son enregistrement.
        $manager->persist($user);

        // Initialise Faker avec des données françaises.
        $faker = Factory::create('fr_FR');

        // Utilise une valeur fixe pour obtenir des données reproductibles.
        $faker->seed(20260916);

        // Crée neuf clients supplémentaires.
        for ($i = 2; $i <= 10; $i++) {
            $client = new User();

            // Génère automatiquement un UUID unique.
            $client->setUuid(
                Uuid::v4()->toRfc4122()
            );

            // Génère le prénom et le nom avec Faker.
            $client->setFirstName(
                $faker->firstName()
            );

            $client->setLastName(
                $faker->lastName()
            );

            // Utilise une adresse e-mail prévisible
            // afin de pouvoir retrouver facilement les clients
            // dans les fixtures de réservations.
            $client->setEmail(
                "client{$i}@mail.fr"
            );

            // Attribue le rôle utilisateur.
            $client->setRoles(['ROLE_USER']);

            // Nombre de convives par défaut.
            $client->setGuestNumber(
                $faker->numberBetween(1, 6)
            );

            // Allergies facultatives.
            $client->setAllergy(
                $faker->optional(0.2)->randomElement([
                    'Aucune',
                    'Allergie aux crustacés',
                    'Allergie aux fruits à coque',
                    'Allergie au gluten',
                ])
            );

            // Utilise le même mot de passe de test
            // pour tous les comptes générés.
            $client->setPassword(
                $this->passwordHasher->hashPassword(
                    $client,
                    'password'
                )
            );

            // Enregistre la date de création.
            $client->setCreatedAt(
                new \DateTimeImmutable()
            );

            // Prépare le client pour son enregistrement.
            $manager->persist($client);
        }

        // Enregistre tous les utilisateurs en base.
        $manager->flush();
    }
}