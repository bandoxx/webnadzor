<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240904082759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE device_document (id INT AUTO_INCREMENT NOT NULL, device_id INT DEFAULT NULL, year VARCHAR(4) NOT NULL, number_of_document VARCHAR(255) NOT NULL, serial_sensor_number VARCHAR(255) NOT NULL, entry INT DEFAULT NULL, file VARCHAR(255) NOT NULL, INDEX IDX_565DD35694A4C7D4 (device_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE device_document ADD CONSTRAINT FK_565DD35694A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_document DROP FOREIGN KEY FK_565DD35694A4C7D4');
        $this->addSql('DROP TABLE device_document');
    }
}
