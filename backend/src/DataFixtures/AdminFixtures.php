<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

class AdminFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Crée l'utilisateur administrateur de test.
        $admin = new User();

        // Génère automatiquement un UUID unique.
        $admin->setUuid(
            Uuid::v4()->toRfc4122()
        );

        // Informations personnelles.
        $admin->setFirstName('Admin');
        $admin->setLastName('Quai Antique');
        $admin->setEmail('admin@mail.fr');

        // Attribue le rôle administrateur.
        $admin->setRoles(['ROLE_ADMIN']);

        // Nombre de convives par défaut.
        $admin->setGuestNumber(1);

        // Allergies facultatives.
        $admin->setAllergy(null);

        // Mot de passe en clair uniquement à cette étape.
        // Il sera automatiquement haché avant d'être enregistré.
        $admin->setPassword(
            $this->passwordHasher->hashPassword(
                $admin,
                'password'
            )
        );

        // Enregistre la date de création.
        $admin->setCreatedAt(
            new \DateTimeImmutable()
        );

        // Enregistre l'administrateur en base de données.
        $manager->persist($admin);
        $manager->flush();
    }
}