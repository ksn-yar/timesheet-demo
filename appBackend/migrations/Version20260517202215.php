<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260517202215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tickets ADD rate_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4BC999F9F FOREIGN KEY (rate_id) REFERENCES rates (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX idx_tickets_rate_id ON tickets (rate_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF4BC999F9F');
        $this->addSql('DROP INDEX idx_tickets_rate_id');
        $this->addSql('ALTER TABLE tickets DROP rate_id');
    }
}
