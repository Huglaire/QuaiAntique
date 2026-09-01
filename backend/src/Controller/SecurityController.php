<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SecurityController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        // Récupère les données JSON envoyées dans la requête.
        $data = json_decode($request->getContent(), true);

        // Vérifie que les données reçues sont bien au format attendu.
        if (!is_array($data)) {
            return new JsonResponse([
                'message' => 'Les données envoyées sont invalides.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifie la présence des champs obligatoires.
        $requiredFields = [
            'firstName',
            'lastName',
            'email',
            'password',
            'guestNumber',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return new JsonResponse([
                    'message' => sprintf(
                        'Le champ "%s" est obligatoire.',
                        $field
                    )
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Vérifie si l'adresse e-mail est déjà utilisée.
        $existingUser = $entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'email' => $data['email'],
            ]);

        if ($existingUser !== null) {
            return new JsonResponse([
                'message' => 'Cette adresse e-mail est déjà utilisée.'
            ], JsonResponse::HTTP_CONFLICT);
        }

        // Crée un nouvel utilisateur.
        $user = new User();

        // Génère automatiquement un UUID unique.
        $user->setUuid(
            Uuid::v4()->toRfc4122()
        );

        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setEmail($data['email']);
        $user->setGuestNumber((int) $data['guestNumber']);

        // Le champ allergie est facultatif.
        $user->setAllergy(
            $data['allergy'] ?? null
        );

        // Attribue le rôle client par défaut.
        $user->setRoles([
            'ROLE_USER',
        ]);

        // Hache le mot de passe avant son enregistrement en base de données.
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $data['password']
        );

        $user->setPassword($hashedPassword);

        // Enregistre la date de création.
        $user->setCreatedAt(
            new \DateTimeImmutable()
        );

        // Enregistre l'utilisateur en base de données.
        $entityManager->persist($user);
        $entityManager->flush();

        // Retourne les informations non sensibles du nouvel utilisateur.
        return new JsonResponse([
            'message' => 'Utilisateur créé avec succès.',
            'user' => [
                'uuid' => $user->getUuid(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'guestNumber' => $user->getGuestNumber(),
                'allergy' => $user->getAllergy(),
                'roles' => $user->getRoles(),
            ],
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(
        #[CurrentUser] ?User $user
    ): JsonResponse {
        // Vérifie qu'un utilisateur authentifié est disponible.
        if ($user === null) {
            return new JsonResponse([
                'message' => 'Utilisateur non authentifié.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Retourne les informations de l'utilisateur connecté.
        return new JsonResponse([
            'uuid' => $user->getUuid(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'guestNumber' => $user->getGuestNumber(),
            'allergy' => $user->getAllergy(),
            'roles' => $user->getRoles(),
        ]);
    }
}