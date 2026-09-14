<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute les heures de fermeture du restaurant.
 */
final class Version20260914144517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les heures de fermeture du midi et du soir au restaurant.';
    }

    public function up(Schema $schema): void
    {
        // Ajoute les nouvelles colonnes temporairement nullable.
        $this->addSql(
            'ALTER TABLE restaurant
             ADD lunch_closing_time TIME DEFAULT NULL,
             ADD dinner_closing_time TIME DEFAULT NULL'
        );

        // Conserve les horaires actuels en utilisant une fermeture
        // deux heures après l'ouverture pour les données existantes.
        $this->addSql(
            'UPDATE restaurant
             SET lunch_closing_time = ADDTIME(lunch_opening_time, \'02:00:00\'),
                 dinner_closing_time = ADDTIME(dinner_opening_time, \'02:00:00\')'
        );

        // Rend les nouvelles colonnes obligatoires après leur initialisation.
        $this->addSql(
            'ALTER TABLE restaurant
             MODIFY lunch_closing_time TIME NOT NULL,
             MODIFY dinner_closing_time TIME NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        // Supprime les deux heures de fermeture.
        $this->addSql(
            'ALTER TABLE restaurant
             DROP lunch_closing_time,
             DROP dinner_closing_time'
        );
    }
}