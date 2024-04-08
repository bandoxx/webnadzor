<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240407155516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD map_active TINYINT(1) NOT NULL, ADD temperature_active TINYINT(1) NOT NULL');
        $this->addSql('DROP INDEX device_id ON device_data');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP map_active, DROP temperature_active');
        $this->addSql('CREATE INDEX device_id ON device_data (device_id, device_date)');
    }
}
