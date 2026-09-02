<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
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
        // Crée l'utilisateur de test.
        $user = new User();

        // Génère automatiquement un UUID unique.
        $user->setUuid(
            Uuid::v4()->toRfc4122()
        );

        // Informations personnelles.
        $user->setFirstName('Hugo');
        $user->setLastName('Test');

        // Adresse e-mail utilisée pour les tests.
        $user->setEmail('hugo@mail.fr');

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

        // Enregistre l'utilisateur en base de données.
        $manager->flush();
    }
}