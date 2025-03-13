<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313135753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDECCFA12B8');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDECCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD recipient_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES profile (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_BF5476CAE92F8F78 ON notification (recipient_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE92F8F78');
        $this->addSql('DROP INDEX IDX_BF5476CAE92F8F78 ON notification');
        $this->addSql('ALTER TABLE notification DROP recipient_id');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDECCFA12B8');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDECCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
    }
}
