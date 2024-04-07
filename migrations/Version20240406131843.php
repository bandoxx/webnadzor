<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240406131843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_data ADD d1 TINYINT(1) NOT NULL, ADD t1 NUMERIC(4, 2) DEFAULT NULL, ADD rh1 NUMERIC(5, 2) DEFAULT NULL, ADD mkt1 NUMERIC(4, 2) DEFAULT NULL, ADD avrg1 NUMERIC(4, 2) DEFAULT NULL, ADD t_min1 NUMERIC(4, 2) DEFAULT NULL, ADD t_max1 NUMERIC(4, 2) DEFAULT NULL, ADD note1 VARCHAR(255) DEFAULT NULL, ADD d2 TINYINT(1) NOT NULL, ADD t2 NUMERIC(4, 2) DEFAULT NULL, ADD rh2 NUMERIC(5, 2) DEFAULT NULL, ADD mkt2 NUMERIC(4, 2) DEFAULT NULL, ADD avrg2 NUMERIC(4, 2) DEFAULT NULL, ADD t_min2 NUMERIC(4, 2) DEFAULT NULL, ADD t_max2 NUMERIC(4, 2) DEFAULT NULL, ADD note2 VARCHAR(255) DEFAULT NULL, DROP entry1, DROP entry2');
        $this->addSql('ALTER TABLE device_data_entry DROP INDEX IDX_E2AD1229BD4378A1, ADD UNIQUE INDEX UNIQ_E2AD1229BD4378A1 (device_data_id)');
        $this->addSql('ALTER TABLE device_data_entry DROP entry');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_data ADD entry1 JSON DEFAULT NULL, ADD entry2 JSON DEFAULT NULL, DROP d1, DROP t1, DROP rh1, DROP mkt1, DROP avrg1, DROP t_min1, DROP t_max1, DROP note1, DROP d2, DROP t2, DROP rh2, DROP mkt2, DROP avrg2, DROP t_min2, DROP t_max2, DROP note2');
        $this->addSql('ALTER TABLE device_data_entry DROP INDEX UNIQ_E2AD1229BD4378A1, ADD INDEX IDX_E2AD1229BD4378A1 (device_data_id)');
        $this->addSql('ALTER TABLE device_data_entry ADD entry INT NOT NULL');
    }
}
