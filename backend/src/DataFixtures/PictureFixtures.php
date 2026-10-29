<?php

namespace App\DataFixtures;

use App\Entity\Picture;
use App\Repository\RestaurantRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class PictureFixtures extends Fixture implements DependentFixtureInterface
{
    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];

    public function __construct(
        private RestaurantRepository $restaurantRepository,
        private SluggerInterface $slugger
    ) {
    }

    /**
     * Indique les fixtures qui doivent être exécutées avant celle-ci.
     */
    public function getDependencies(): array
    {
        return [
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

        // Définit le dossier contenant les images sources des fixtures.
        $sourceDirectory = dirname(__DIR__, 2)
            . '/fixtures/gallery';

        // Définit le dossier utilisé par l'application
        // pour servir les images de la galerie.
        $uploadDirectory = dirname(__DIR__, 2)
            . '/public/uploads/gallery';

        // Vérifie que le dossier des images sources existe.
        if (!is_dir($sourceDirectory)) {
            throw new \RuntimeException(
                'Le dossier des images de fixtures est introuvable : '
                . $sourceDirectory
            );
        }

        // Crée le dossier de destination s'il n'existe pas.
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0775, true);
        }

        // Récupère les fichiers présents dans le dossier des fixtures.
        $files = scandir($sourceDirectory);

        if ($files === false) {
            throw new \RuntimeException(
                'Impossible de lire le dossier des images de fixtures.'
            );
        }

        foreach ($files as $fileName) {
            // Ignore les entrées qui ne sont pas des fichiers.
            if (
                $fileName === '.'
                || $fileName === '..'
            ) {
                continue;
            }

            $sourcePath =
                $sourceDirectory . '/' . $fileName;

            if (!is_file($sourcePath)) {
                continue;
            }

            // Récupère l'extension du fichier.
            $extension =
                strtolower(
                    pathinfo(
                        $fileName,
                        PATHINFO_EXTENSION
                    )
                );

            // Ignore les fichiers qui ne sont pas des images acceptées.
            if (
                !in_array(
                    $extension,
                    self::ALLOWED_EXTENSIONS,
                    true
                )
            ) {
                continue;
            }

            // Récupère le nom du fichier sans son extension.
            $fileTitle =
                pathinfo(
                    $fileName,
                    PATHINFO_FILENAME
                );

            // Transforme les séparateurs courants en espaces
            // pour obtenir un titre lisible.
            $title =
                str_replace(
                    ['_', '-'],
                    ' ',
                    $fileTitle
                );

            $title =
                trim(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        $title
                    ) ?? $title
                );

            // Met la première lettre en majuscule.
            $title =
                mb_strtoupper(
                    mb_substr($title, 0, 1)
                )
                . mb_substr($title, 1);

            // Génère un slug à partir du titre.
            $slug =
                $this->slugger
                    ->slug($title)
                    ->lower()
                    ->toString();

            // Utilise le nom original du fichier comme imageName.
            $imageName = $fileName;

            $destinationPath =
                $uploadDirectory . '/' . $imageName;

            // Copie l'image vers le dossier public utilisé
            // par l'application.
            if (!copy($sourcePath, $destinationPath)) {
                throw new \RuntimeException(
                    "Impossible de copier l'image {$fileName}."
                );
            }

            // Crée l'entrée Picture correspondante.
            $picture = new Picture();

            $picture->setTitle($title);
            $picture->setSlug($slug);
            $picture->setImageName($imageName);
            $picture->setRestaurant($restaurant);
            $picture->setCreatedAt(
                new \DateTimeImmutable()
            );

            // Prépare la photo pour son enregistrement.
            $manager->persist($picture);
        }

        // Enregistre toutes les photos en base.
        $manager->flush();
    }
}