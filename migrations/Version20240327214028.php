<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240327214028 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_icon ADD client_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE device_icon ADD CONSTRAINT FK_ECADFCE119EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('CREATE INDEX IDX_ECADFCE119EB6921 ON device_icon (client_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_icon DROP FOREIGN KEY FK_ECADFCE119EB6921');
        $this->addSql('DROP INDEX IDX_ECADFCE119EB6921 ON device_icon');
        $this->addSql('ALTER TABLE device_icon DROP client_id');
    }
}
