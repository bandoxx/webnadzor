<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240324013033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, xml_name VARCHAR(255) NOT NULL, parser_active TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, t1_location VARCHAR(255) NOT NULL, t1_name VARCHAR(255) NOT NULL, t1_unit VARCHAR(255) NOT NULL, t1_min NUMERIC(5, 2) NOT NULL, t1_max NUMERIC(5, 2) NOT NULL, t1_image TINYINT(1) NOT NULL, t1_show_chart TINYINT(1) NOT NULL, INDEX IDX_92FB68E19EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device_data (id INT AUTO_INCREMENT NOT NULL, device_id INT NOT NULL, server_date DATETIME NOT NULL, device_date DATETIME NOT NULL, gsm_signal INT NOT NULL, supply TINYINT(1) NOT NULL, vbat NUMERIC(2, 1) NOT NULL, battery INT NOT NULL, temperature1 NUMERIC(4, 2) DEFAULT NULL, temperature2 NUMERIC(4, 2) DEFAULT NULL, relative_humidity1 NUMERIC(5, 2) DEFAULT NULL, relative_humidity2 NUMERIC(5, 2) DEFAULT NULL, digital_entry1 TINYINT(1) NOT NULL, digital_entry2 TINYINT(1) NOT NULL, mean_kinetic_temperature1 NUMERIC(4, 2) DEFAULT NULL, mean_kinetic_temperature2 NUMERIC(4, 2) DEFAULT NULL, temperature1average NUMERIC(4, 2) DEFAULT NULL, temperature1max NUMERIC(4, 2) DEFAULT NULL, temperature1min NUMERIC(4, 2) DEFAULT NULL, temperature2average NUMERIC(4, 2) DEFAULT NULL, temperature2max NUMERIC(4, 2) DEFAULT NULL, temperature2min NUMERIC(4, 2) DEFAULT NULL, INDEX IDX_24CA265994A4C7D4 (device_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ftp_config (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, host VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, INDEX IDX_8EB9088919EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE login_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, client_id INT DEFAULT NULL, server_date DATETIME DEFAULT NULL, status INT DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, ip VARCHAR(255) DEFAULT NULL, host VARCHAR(255) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL, os VARCHAR(255) DEFAULT NULL, browser VARCHAR(255) DEFAULT NULL, INDEX IDX_F16D9FFFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, from_old_system TINYINT(1) NOT NULL, password VARCHAR(255) NOT NULL, permission INT NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), INDEX IDX_8D93D64919EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE device ADD CONSTRAINT FK_92FB68E19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE device_data ADD CONSTRAINT FK_24CA265994A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE ftp_config ADD CONSTRAINT FK_8EB9088919EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE login_log ADD CONSTRAINT FK_F16D9FFFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64919EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device DROP FOREIGN KEY FK_92FB68E19EB6921');
        $this->addSql('ALTER TABLE device_data DROP FOREIGN KEY FK_24CA265994A4C7D4');
        $this->addSql('ALTER TABLE ftp_config DROP FOREIGN KEY FK_8EB9088919EB6921');
        $this->addSql('ALTER TABLE login_log DROP FOREIGN KEY FK_F16D9FFFA76ED395');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64919EB6921');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE device');
        $this->addSql('DROP TABLE device_data');
        $this->addSql('DROP TABLE ftp_config');
        $this->addSql('DROP TABLE login_log');
        $this->addSql('DROP TABLE user');
    }
}
