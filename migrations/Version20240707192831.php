<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240707192831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_data CHANGE id id INT AUTO_INCREMENT NOT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE device_data ADD CONSTRAINT FK_24CA265994A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('CREATE INDEX IDX_24CA265994A4C7D4 ON device_data (device_id)');
        $this->addSql('CREATE INDEX idx_search ON device_data (device_id, device_date)');
        $this->addSql('ALTER TABLE device_data_archive ADD CONSTRAINT FK_C382ECBC94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE device_icon ADD CONSTRAINT FK_ECADFCE119EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE login_log ADD CONSTRAINT FK_F16D9FFFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE login_log ADD CONSTRAINT FK_F16D9FFF19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE login_log_archive ADD CONSTRAINT FK_5F41D9BB19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64919EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE user_client ADD CONSTRAINT FK_A2161F68A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_client ADD CONSTRAINT FK_A2161F6819EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_device_access ADD CONSTRAINT FK_3BF7F71FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_device_access ADD CONSTRAINT FK_3BF7F71F94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE user_device_access ADD CONSTRAINT FK_3BF7F71F19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE device_data MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE device_data DROP FOREIGN KEY FK_24CA265994A4C7D4');
        $this->addSql('DROP INDEX IDX_24CA265994A4C7D4 ON device_data');
        $this->addSql('DROP INDEX `primary` ON device_data');
        $this->addSql('DROP INDEX idx_search ON device_data');
        $this->addSql('ALTER TABLE device_data CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE device_data_archive DROP FOREIGN KEY FK_C382ECBC94A4C7D4');
        $this->addSql('ALTER TABLE device_icon DROP FOREIGN KEY FK_ECADFCE119EB6921');
        $this->addSql('ALTER TABLE login_log DROP FOREIGN KEY FK_F16D9FFFA76ED395');
        $this->addSql('ALTER TABLE login_log DROP FOREIGN KEY FK_F16D9FFF19EB6921');
        $this->addSql('ALTER TABLE login_log_archive DROP FOREIGN KEY FK_5F41D9BB19EB6921');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64919EB6921');
        $this->addSql('ALTER TABLE user_client DROP FOREIGN KEY FK_A2161F68A76ED395');
        $this->addSql('ALTER TABLE user_client DROP FOREIGN KEY FK_A2161F6819EB6921');
        $this->addSql('ALTER TABLE user_device_access DROP FOREIGN KEY FK_3BF7F71FA76ED395');
        $this->addSql('ALTER TABLE user_device_access DROP FOREIGN KEY FK_3BF7F71F94A4C7D4');
        $this->addSql('ALTER TABLE user_device_access DROP FOREIGN KEY FK_3BF7F71F19EB6921');
    }
}
