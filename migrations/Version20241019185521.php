<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241019185521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alarm_device_setup (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device_alarm_setup_entry (id INT AUTO_INCREMENT NOT NULL, device_id INT NOT NULL, entry INT NOT NULL, phone_number VARCHAR(15) DEFAULT NULL, is_temperature_active TINYINT(1) NOT NULL, is_humidity_active TINYINT(1) NOT NULL, is_digital_entry_active TINYINT(1) NOT NULL, digital_entry_alarm_value TINYINT(1) NOT NULL, is_sms_active TINYINT(1) NOT NULL, is_voice_message_active TINYINT(1) NOT NULL, INDEX IDX_177CD2994A4C7D4 (device_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device_alarm_setup_general (id INT AUTO_INCREMENT NOT NULL, device_id INT NOT NULL, phone_number VARCHAR(15) DEFAULT NULL, is_device_power_supply_off_active TINYINT(1) NOT NULL, is_sms_active TINYINT(1) NOT NULL, is_voice_message_active TINYINT(1) NOT NULL, INDEX IDX_CE3BABF594A4C7D4 (device_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE device_alarm_setup_entry ADD CONSTRAINT FK_177CD2994A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE device_alarm_setup_general ADD CONSTRAINT FK_CE3BABF594A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_alarm_setup_entry DROP FOREIGN KEY FK_177CD2994A4C7D4');
        $this->addSql('ALTER TABLE device_alarm_setup_general DROP FOREIGN KEY FK_CE3BABF594A4C7D4');
        $this->addSql('DROP TABLE alarm_device_setup');
        $this->addSql('DROP TABLE device_alarm_setup_entry');
        $this->addSql('DROP TABLE device_alarm_setup_general');
    }
}
