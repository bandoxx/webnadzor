<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240324220455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device ADD entry1 JSON DEFAULT NULL, ADD entry2 JSON DEFAULT NULL, DROP t1_location, DROP t1_name, DROP t1_unit, DROP t1_min, DROP t1_max, DROP t1_image, DROP t1_show_chart');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device ADD t1_location VARCHAR(255) NOT NULL, ADD t1_name VARCHAR(255) NOT NULL, ADD t1_unit VARCHAR(255) NOT NULL, ADD t1_min NUMERIC(5, 2) NOT NULL, ADD t1_max NUMERIC(5, 2) NOT NULL, ADD t1_image TINYINT(1) NOT NULL, ADD t1_show_chart TINYINT(1) NOT NULL, DROP entry1, DROP entry2');
    }
}
