<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240406160444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_data_entry DROP FOREIGN KEY FK_E2AD1229BD4378A1');
        $this->addSql('DROP TABLE device_data_entry');
        $this->addSql('ALTER TABLE device_data ADD t_avrg1 NUMERIC(4, 2) DEFAULT NULL, ADD t_avrg2 NUMERIC(4, 2) DEFAULT NULL, DROP avrg1, DROP avrg2');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE device_data_entry (id INT AUTO_INCREMENT NOT NULL, device_data_id INT DEFAULT NULL, t NUMERIC(4, 2) DEFAULT NULL, rh NUMERIC(5, 2) DEFAULT NULL, mkt NUMERIC(4, 2) DEFAULT NULL, avrg NUMERIC(4, 2) DEFAULT NULL, t_min NUMERIC(4, 2) DEFAULT NULL, t_max NUMERIC(4, 2) DEFAULT NULL, note VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_E2AD1229BD4378A1 (device_data_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE device_data_entry ADD CONSTRAINT FK_E2AD1229BD4378A1 FOREIGN KEY (device_data_id) REFERENCES device_data (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE device_data ADD avrg1 NUMERIC(4, 2) DEFAULT NULL, ADD avrg2 NUMERIC(4, 2) DEFAULT NULL, DROP t_avrg1, DROP t_avrg2');
    }
}
