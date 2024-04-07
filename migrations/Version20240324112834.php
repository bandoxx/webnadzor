<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240324112834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_data ADD entry1 JSON DEFAULT NULL, ADD entry2 JSON DEFAULT NULL, DROP temperature1, DROP temperature2, DROP relative_humidity1, DROP relative_humidity2, DROP digital_entry1, DROP digital_entry2, DROP mean_kinetic_temperature1, DROP mean_kinetic_temperature2, DROP temperature1average, DROP temperature1max, DROP temperature1min, DROP temperature2average, DROP temperature2max, DROP temperature2min');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_data ADD temperature1 NUMERIC(4, 2) DEFAULT NULL, ADD temperature2 NUMERIC(4, 2) DEFAULT NULL, ADD relative_humidity1 NUMERIC(5, 2) DEFAULT NULL, ADD relative_humidity2 NUMERIC(5, 2) DEFAULT NULL, ADD digital_entry1 TINYINT(1) NOT NULL, ADD digital_entry2 TINYINT(1) NOT NULL, ADD mean_kinetic_temperature1 NUMERIC(4, 2) DEFAULT NULL, ADD mean_kinetic_temperature2 NUMERIC(4, 2) DEFAULT NULL, ADD temperature1average NUMERIC(4, 2) DEFAULT NULL, ADD temperature1max NUMERIC(4, 2) DEFAULT NULL, ADD temperature1min NUMERIC(4, 2) DEFAULT NULL, ADD temperature2average NUMERIC(4, 2) DEFAULT NULL, ADD temperature2max NUMERIC(4, 2) DEFAULT NULL, ADD temperature2min NUMERIC(4, 2) DEFAULT NULL, DROP entry1, DROP entry2');
    }
}
