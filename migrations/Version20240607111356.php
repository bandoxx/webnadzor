<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240607111356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_setting ADD is_temperature_alarm_active TINYINT(1) NOT NULL, ADD is_humidity_alarm_active TINYINT(1) NOT NULL');
        $this->addSql('UPDATE client_setting SET is_temperature_alarm_active = true, is_humidity_alarm_active = true');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_setting DROP is_temperature_alarm_active, DROP is_humidity_alarm_active');
    }
}
