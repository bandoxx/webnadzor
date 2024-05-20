<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240520113626 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_setting ADD is_battery_level_alarm_active TINYINT(1) NOT NULL, ADD device_signal_alarm INT NOT NULL, ADD is_device_signal_alarm_active TINYINT(1) NOT NULL, ADD is_device_offline_alarm_active TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_setting DROP is_battery_level_alarm_active, DROP device_signal_alarm, DROP is_device_signal_alarm_active, DROP is_device_offline_alarm_active');
    }
}
