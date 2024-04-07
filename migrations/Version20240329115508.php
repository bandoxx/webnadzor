<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240329115508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE login_log_archive (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, server_date DATETIME NOT NULL, archive_date DATE NOT NULL, filename VARCHAR(255) NOT NULL, INDEX IDX_5F41D9BB19EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE login_log_archive ADD CONSTRAINT FK_5F41D9BB19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE login_log_archive DROP FOREIGN KEY FK_5F41D9BB19EB6921');
        $this->addSql('DROP TABLE login_log_archive');
    }
}
