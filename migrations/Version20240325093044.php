<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240325093044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client_info (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, host VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, INDEX IDX_1A15F23519EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device_alarm (id INT AUTO_INCREMENT NOT NULL, device_id INT NOT NULL, server_date DATETIME NOT NULL, device_date DATETIME NOT NULL, end_server_date DATETIME DEFAULT NULL, end_device_date DATETIME NOT NULL, sensor VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, INDEX IDX_B21AA6BA94A4C7D4 (device_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client_info ADD CONSTRAINT FK_1A15F23519EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE device_alarm ADD CONSTRAINT FK_B21AA6BA94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_info DROP FOREIGN KEY FK_1A15F23519EB6921');
        $this->addSql('ALTER TABLE device_alarm DROP FOREIGN KEY FK_B21AA6BA94A4C7D4');
        $this->addSql('DROP TABLE client_info');
        $this->addSql('DROP TABLE device_alarm');
    }
}
