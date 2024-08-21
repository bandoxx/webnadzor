<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240821084947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client_storage (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, image VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_6475F4919EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE client_storage_device (id INT AUTO_INCREMENT NOT NULL, client_storage_id INT NOT NULL, device_id INT NOT NULL, entry INT NOT NULL, font_size INT NOT NULL, font_color VARCHAR(7) NOT NULL, position_x INT NOT NULL, position_y INT NOT NULL, INDEX IDX_13EF0000CC91E6D1 (client_storage_id), INDEX IDX_13EF000094A4C7D4 (device_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE client_storage_text (id INT AUTO_INCREMENT NOT NULL, client_storage_id INT NOT NULL, font_size INT NOT NULL, font_color VARCHAR(7) NOT NULL, text VARCHAR(255) NOT NULL, position_x INT NOT NULL, position_y INT NOT NULL, INDEX IDX_83ACCED3CC91E6D1 (client_storage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client_storage ADD CONSTRAINT FK_6475F4919EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE client_storage_device ADD CONSTRAINT FK_13EF0000CC91E6D1 FOREIGN KEY (client_storage_id) REFERENCES client_storage (id)');
        $this->addSql('ALTER TABLE client_storage_device ADD CONSTRAINT FK_13EF000094A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE client_storage_text ADD CONSTRAINT FK_83ACCED3CC91E6D1 FOREIGN KEY (client_storage_id) REFERENCES client_storage (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_storage DROP FOREIGN KEY FK_6475F4919EB6921');
        $this->addSql('ALTER TABLE client_storage_device DROP FOREIGN KEY FK_13EF0000CC91E6D1');
        $this->addSql('ALTER TABLE client_storage_device DROP FOREIGN KEY FK_13EF000094A4C7D4');
        $this->addSql('ALTER TABLE client_storage_text DROP FOREIGN KEY FK_83ACCED3CC91E6D1');
        $this->addSql('DROP TABLE client_storage');
        $this->addSql('DROP TABLE client_storage_device');
        $this->addSql('DROP TABLE client_storage_text');
    }
}
