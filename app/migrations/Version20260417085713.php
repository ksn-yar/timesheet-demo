<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417085713 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE change_requests ADD CONSTRAINT FK_D47FCABF166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A419EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE rates ADD CONSTRAINT FK_44D4AB3CD60322AC FOREIGN KEY (role_id) REFERENCES roles (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE rates ADD CONSTRAINT FK_44D4AB3CBB3453DB FOREIGN KEY (work_id) REFERENCES works (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_5058659740868EB5 FOREIGN KEY (cr_id) REFERENCES change_requests (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF48C03F15C FOREIGN KEY (employee_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF48DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4BB3453DB FOREIGN KEY (work_id) REFERENCES works (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9D60322AC FOREIGN KEY (role_id) REFERENCES roles (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE change_requests DROP CONSTRAINT FK_D47FCABF166D1F9C');
        $this->addSql('ALTER TABLE projects DROP CONSTRAINT FK_5C93B3A419EB6921');
        $this->addSql('ALTER TABLE rates DROP CONSTRAINT FK_44D4AB3CD60322AC');
        $this->addSql('ALTER TABLE rates DROP CONSTRAINT FK_44D4AB3CBB3453DB');
        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_50586597166D1F9C');
        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_5058659740868EB5');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF48C03F15C');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF48DB60186');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF4BB3453DB');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E9FE54D947');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E9D60322AC');
    }
}
