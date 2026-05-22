<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416193148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE report_exports (id UUID NOT NULL, report_ids JSON NOT NULL, format VARCHAR(10) NOT NULL, generated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_ref VARCHAR(1024) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_report_exports_format ON report_exports (format)');
        $this->addSql('CREATE INDEX idx_report_exports_generated_at ON report_exports (generated_at)');
        $this->addSql('CREATE TABLE reports (id UUID NOT NULL, name VARCHAR(255) NOT NULL, created_by UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, period_from DATE NOT NULL, period_to DATE NOT NULL, filters JSON NOT NULL, group_by JSON NOT NULL, data JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_reports_created_by ON reports (created_by)');
        $this->addSql('CREATE INDEX idx_reports_period_from ON reports (period_from)');
        $this->addSql('CREATE INDEX idx_reports_period_to ON reports (period_to)');
        $this->addSql('CREATE INDEX idx_reports_created_at ON reports (created_at)');
        $this->addSql('CREATE INDEX idx_reports_name ON reports (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE report_exports');
        $this->addSql('DROP TABLE reports');
    }
}
