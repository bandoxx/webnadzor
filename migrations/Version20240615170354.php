<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240615170354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD deleted_by_user_id INT DEFAULT NULL, DROP deleted_by_user');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455FCF2A97A FOREIGN KEY (deleted_by_user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_C7440455FCF2A97A ON client (deleted_by_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455FCF2A97A');
        $this->addSql('DROP INDEX IDX_C7440455FCF2A97A ON client');
        $this->addSql('ALTER TABLE client ADD deleted_by_user VARCHAR(255) DEFAULT NULL, DROP deleted_by_user_id');
    }
}
