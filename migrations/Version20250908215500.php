<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250908215500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create admin_overview_cache table for cached Admin Overview metrics';
    }

    public function up(Schema $schema): void
    {
        // MySQL table with JSON column (as used elsewhere in the project)
        $this->addSql('CREATE TABLE admin_overview_cache (
            id INT AUTO_INCREMENT NOT NULL,
            client_id INT NOT NULL,
            number_of_devices INT NOT NULL,
            online_devices INT NOT NULL,
            offline_devices INT NOT NULL,
            alarms JSON NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_AOC_CLIENT (client_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE admin_overview_cache ADD CONSTRAINT FK_AOC_CLIENT FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_overview_cache DROP FOREIGN KEY FK_AOC_CLIENT');
        $this->addSql('DROP TABLE admin_overview_cache');
    }
}
