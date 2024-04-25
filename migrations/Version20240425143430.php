<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240425143430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE device_data_archive SET period = "daily" WHERE period = "day"');
        $this->addSql('UPDATE device_data_archive SET period = "monthly" WHERE period = "month"');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE device_data_archive SET period = "day" WHERE period = "daily"');
        $this->addSql('UPDATE device_data_archive SET period = "month" WHERE period = "monthly"');
    }
}
