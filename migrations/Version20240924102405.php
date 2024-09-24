<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240924102405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client_storage_digital_entry (id INT AUTO_INCREMENT NOT NULL, client_storage_id INT NOT NULL, device_id INT NOT NULL, entry INT NOT NULL, font_size INT NOT NULL, font_color_on VARCHAR(7) NOT NULL, font_color_off VARCHAR(7) NOT NULL, position_x INT NOT NULL, position_y INT NOT NULL, text_on VARCHAR(255) NOT NULL, text_off VARCHAR(255) NOT NULL, background_active TINYINT(1) NOT NULL, INDEX IDX_F10F617CC91E6D1 (client_storage_id), INDEX IDX_F10F61794A4C7D4 (device_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client_storage_digital_entry ADD CONSTRAINT FK_F10F617CC91E6D1 FOREIGN KEY (client_storage_id) REFERENCES client_storage (id)');
        $this->addSql('ALTER TABLE client_storage_digital_entry ADD CONSTRAINT FK_F10F61794A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_storage_digital_entry DROP FOREIGN KEY FK_F10F617CC91E6D1');
        $this->addSql('ALTER TABLE client_storage_digital_entry DROP FOREIGN KEY FK_F10F61794A4C7D4');
        $this->addSql('DROP TABLE client_storage_digital_entry');
    }
}
