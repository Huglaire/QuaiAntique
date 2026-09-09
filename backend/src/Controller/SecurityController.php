<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

#[OA\Tag(
    name: 'Authentification et compte',
    description: 'Inscription et gestion du compte utilisateur connecté.'
)]
final class SecurityController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Créer un compte utilisateur',
        description: 'Crée un nouveau compte client. Le rôle ROLE_USER est attribué automatiquement.',
        tags: ['Authentification et compte'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur créé avec succès.'
            ),
            new OA\Response(
                response: 400,
                description: 'Les données envoyées sont invalides ou un champ obligatoire est manquant.'
            ),
            new OA\Response(
                response: 409,
                description: 'Cette adresse e-mail est déjà utilisée.'
            ),
        ]
    )]
    #[OA\RequestBody(
        description: 'Informations nécessaires à la création du compte.',
        required: true,
        content: new OA\JsonContent(
            required: [
                'firstName',
                'lastName',
                'email',
                'password',
                'guestNumber',
            ],
            properties: [
                new OA\Property(
                    property: 'firstName',
                    type: 'string',
                    example: 'Hugo'
                ),
                new OA\Property(
                    property: 'lastName',
                    type: 'string',
                    example: 'Pollon'
                ),
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    example: 'hugo@example.fr'
                ),
                new OA\Property(
                    property: 'password',
                    type: 'string',
                    format: 'password',
                    example: 'motdepasse123'
                ),
                new OA\Property(
                    property: 'guestNumber',
                    type: 'integer',
                    example: 2
                ),
                new OA\Property(
                    property: 'allergy',
                    type: 'string',
                    example: 'Arachides',
                    nullable: true
                ),
            ]
        )
    )]
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
    #[OA\Get(
        path: '/api/me',
        summary: 'Récupérer le compte connecté',
        description: 'Retourne les informations du compte correspondant au JWT fourni.',
        tags: ['Authentification et compte'],
        security: [
            ['bearerAuth' => []],
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Informations de l’utilisateur connecté.'
            ),
            new OA\Response(
                response: 401,
                description: 'Utilisateur non authentifié.'
            ),
        ]
    )]
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

    #[Route('/api/me', name: 'api_me_update', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/me',
        summary: 'Modifier le compte connecté',
        description: 'Modifie les informations autorisées du compte utilisateur connecté.',
        tags: ['Authentification et compte'],
        security: [
            ['bearerAuth' => []],
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Utilisateur modifié avec succès.'
            ),
            new OA\Response(
                response: 400,
                description: 'Les données envoyées sont invalides.'
            ),
            new OA\Response(
                response: 401,
                description: 'Utilisateur non authentifié.'
            ),
            new OA\Response(
                response: 409,
                description: 'Cette adresse e-mail est déjà utilisée.'
            ),
        ]
    )]
    #[OA\RequestBody(
        description: 'Champs du compte à modifier. Tous les champs sont facultatifs.',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'firstName',
                    type: 'string',
                    example: 'Hugo'
                ),
                new OA\Property(
                    property: 'lastName',
                    type: 'string',
                    example: 'Pollon'
                ),
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    example: 'nouvelle-adresse@example.fr'
                ),
                new OA\Property(
                    property: 'password',
                    type: 'string',
                    format: 'password',
                    example: 'nouveauMotDePasse123'
                ),
                new OA\Property(
                    property: 'guestNumber',
                    type: 'integer',
                    example: 4
                ),
                new OA\Property(
                    property: 'allergy',
                    type: 'string',
                    example: 'Aucune',
                    nullable: true
                ),
            ]
        )
    )]
    public function update(
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        // Vérifie qu'un utilisateur authentifié est disponible.
        if ($user === null) {
            return new JsonResponse([
                'message' => 'Utilisateur non authentifié.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Récupère les données JSON envoyées dans la requête.
        $data = json_decode($request->getContent(), true);

        // Vérifie que les données reçues sont bien au format attendu.
        if (!is_array($data)) {
            return new JsonResponse([
                'message' => 'Les données envoyées sont invalides.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Modifie uniquement les champs autorisés.
        if (array_key_exists('firstName', $data)) {
            $user->setFirstName($data['firstName']);
        }

        if (array_key_exists('lastName', $data)) {
            $user->setLastName($data['lastName']);
        }

        if (array_key_exists('email', $data)) {
            // Vérifie que la nouvelle adresse e-mail n'est pas déjà utilisée.
            $existingUser = $entityManager
                ->getRepository(User::class)
                ->findOneBy([
                    'email' => $data['email'],
                ]);

            if (
                $existingUser !== null
                && $existingUser->getId() !== $user->getId()
            ) {
                return new JsonResponse([
                    'message' => 'Cette adresse e-mail est déjà utilisée.'
                ], JsonResponse::HTTP_CONFLICT);
            }

            $user->setEmail($data['email']);
        }

        if (array_key_exists('guestNumber', $data)) {
            $user->setGuestNumber((int) $data['guestNumber']);
        }

        if (array_key_exists('allergy', $data)) {
            $user->setAllergy($data['allergy']);
        }

        // Si un nouveau mot de passe est fourni, il est haché avant
        // d'être enregistré en base de données.
        if (
            array_key_exists('password', $data)
            && $data['password'] !== ''
        ) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $data['password']
            );

            $user->setPassword($hashedPassword);
        }

        // Met à jour la date de modification.
        $user->setUpdatedAt(
            new \DateTimeImmutable()
        );

        // Enregistre les modifications.
        $entityManager->flush();

        // Retourne les informations mises à jour de l'utilisateur.
        return new JsonResponse([
            'message' => 'Utilisateur modifié avec succès.',
            'user' => [
                'uuid' => $user->getUuid(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'guestNumber' => $user->getGuestNumber(),
                'allergy' => $user->getAllergy(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    #[Route('/api/me', name: 'api_me_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/me',
        summary: 'Supprimer le compte connecté',
        description: 'Supprime définitivement le compte utilisateur connecté.',
        tags: ['Authentification et compte'],
        security: [
            ['bearerAuth' => []],
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Compte supprimé avec succès.'
            ),
            new OA\Response(
                response: 401,
                description: 'Utilisateur non authentifié.'
            ),
        ]
    )]
    public function delete(
        #[CurrentUser] ?User $user,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifie qu'un utilisateur authentifié est disponible.
        if ($user === null) {
            return new JsonResponse([
                'message' => 'Utilisateur non authentifié.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Supprime le compte utilisateur.
        // Les réservations associées sont conservées grâce à ON DELETE SET NULL.
        $entityManager->remove($user);
        $entityManager->flush();

        // Confirme la suppression du compte.
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}