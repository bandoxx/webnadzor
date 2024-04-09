<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240409151955 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client_image (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, main_logo VARCHAR(255) DEFAULT NULL, pdf_logo VARCHAR(255) DEFAULT NULL, map_marker_icon VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_665098E819EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client_image ADD CONSTRAINT FK_665098E819EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('DROP INDEX idx_search2 ON device_data');
        $this->addSql('DROP INDEX idx_search ON device_data');
        $this->addSql('CREATE INDEX idx_search ON device_data (device_id, device_date)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_image DROP FOREIGN KEY FK_665098E819EB6921');
        $this->addSql('DROP TABLE client_image');
        $this->addSql('DROP INDEX idx_search ON device_data');
        $this->addSql('CREATE INDEX idx_search2 ON device_data (device_id, device_date)');
        $this->addSql('CREATE INDEX idx_search ON device_data (device_id, device_date, t1)');
    }
}
