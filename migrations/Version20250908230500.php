<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250908230500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create device_data_last_cache table to cache last DeviceData per (device, entry)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE device_data_last_cache (
            id INT AUTO_INCREMENT NOT NULL,
            device_id INT NOT NULL,
            entry SMALLINT NOT NULL,
            device_data_id INT NOT NULL,
            device_date DATETIME NOT NULL,
            UNIQUE INDEX uniq_device_entry (device_id, entry),
            INDEX IDX_DDLC_DEVICE_DATA (device_data_id),
            INDEX IDX_DDLC_DEVICE (device_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE device_data_last_cache ADD CONSTRAINT FK_DDLC_DEVICE FOREIGN KEY (device_id) REFERENCES device (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE device_data_last_cache ADD CONSTRAINT FK_DDLC_DEVICE_DATA FOREIGN KEY (device_data_id) REFERENCES device_data (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE device_data_last_cache DROP FOREIGN KEY FK_DDLC_DEVICE');
        $this->addSql('ALTER TABLE device_data_last_cache DROP FOREIGN KEY FK_DDLC_DEVICE_DATA');
        $this->addSql('DROP TABLE device_data_last_cache');
    }
}
