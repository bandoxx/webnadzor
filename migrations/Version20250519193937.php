<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250519193937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_setting CHANGE is_digital_entry_alarm_active is_digital_entry_alarm_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE device ADD serial_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sms_delivery_report CHANGE sent_at sent_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE unresolved_xml CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_setting CHANGE is_digital_entry_alarm_active is_digital_entry_alarm_active TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE device DROP serial_number');
        $this->addSql('ALTER TABLE sms_delivery_report CHANGE sent_at sent_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE unresolved_xml CHANGE created_at created_at DATETIME NOT NULL');
    }
}
