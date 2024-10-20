<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241019211044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE device_alarm_log (id INT AUTO_INCREMENT NOT NULL, device_alarm_id INT DEFAULT NULL, client_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_F82CD668ABB75C51 (device_alarm_id), INDEX IDX_F82CD66819EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE device_alarm_log ADD CONSTRAINT FK_F82CD668ABB75C51 FOREIGN KEY (device_alarm_id) REFERENCES device_alarm (id)');
        $this->addSql('ALTER TABLE device_alarm_log ADD CONSTRAINT FK_F82CD66819EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_alarm_log DROP FOREIGN KEY FK_F82CD668ABB75C51');
        $this->addSql('ALTER TABLE device_alarm_log DROP FOREIGN KEY FK_F82CD66819EB6921');
        $this->addSql('DROP TABLE device_alarm_log');
    }
}
