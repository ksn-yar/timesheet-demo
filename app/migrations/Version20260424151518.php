<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424151518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_users_api_token');
        $this->addSql('DROP INDEX uniq_1483a5e97ba2f5eb');
        $this->addSql('ALTER TABLE users DROP api_token');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD api_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_users_api_token ON users (api_token)');
        $this->addSql('CREATE UNIQUE INDEX uniq_1483a5e97ba2f5eb ON users (api_token)');
    }
}
