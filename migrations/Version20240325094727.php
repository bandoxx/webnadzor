<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240325094727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_device_access (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, device_id INT DEFAULT NULL, INDEX IDX_3BF7F71FA76ED395 (user_id), INDEX IDX_3BF7F71F94A4C7D4 (device_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_device_access ADD CONSTRAINT FK_3BF7F71FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_device_access ADD CONSTRAINT FK_3BF7F71F94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE device ADD old_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_device_access DROP FOREIGN KEY FK_3BF7F71FA76ED395');
        $this->addSql('ALTER TABLE user_device_access DROP FOREIGN KEY FK_3BF7F71F94A4C7D4');
        $this->addSql('DROP TABLE user_device_access');
        $this->addSql('ALTER TABLE device DROP old_id');
    }
}
