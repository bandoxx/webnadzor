<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240524161638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_alarm ADD device_data_id INT DEFAULT NULL, ADD message LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE device_alarm ADD CONSTRAINT FK_B21AA6BABD4378A1 FOREIGN KEY (device_data_id) REFERENCES device_data (id)');
        $this->addSql('CREATE INDEX IDX_B21AA6BABD4378A1 ON device_alarm (device_data_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_alarm DROP FOREIGN KEY FK_B21AA6BABD4378A1');
        $this->addSql('DROP INDEX IDX_B21AA6BABD4378A1 ON device_alarm');
        $this->addSql('ALTER TABLE device_alarm DROP device_data_id, DROP message');
    }
}
