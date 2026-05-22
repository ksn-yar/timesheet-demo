<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260326085205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE TABLE change_requests (id UUID NOT NULL, project_id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))'
        );
        $this->addSql('CREATE INDEX idx_change_requests_project_id ON change_requests (project_id)');
        $this->addSql('CREATE INDEX idx_change_requests_deleted_at ON change_requests (deleted_at)');
        $this->addSql(
            'CREATE TABLE clients (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))'
        );
        $this->addSql('CREATE INDEX idx_clients_deleted_at ON clients (deleted_at)');
        $this->addSql(
            'CREATE TABLE groups (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))'
        );
        $this->addSql('CREATE INDEX idx_groups_deleted_at ON groups (deleted_at)');
        $this->addSql('CREATE UNIQUE INDEX uq_groups_name ON groups (name)');
        $this->addSql(
            'CREATE TABLE import_policies (id UUID NOT NULL, name VARCHAR(255) NOT NULL, source_system VARCHAR(255) NOT NULL, mapping_rules JSON NOT NULL, allow_edit BOOLEAN DEFAULT false NOT NULL, is_active BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))'
        );
        $this->addSql('CREATE INDEX idx_import_policies_source_system ON import_policies (source_system)');
        $this->addSql(
            'CREATE TABLE projects (id UUID NOT NULL, client_id UUID NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, description TEXT DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))'
        );
        $this->addSql('CREATE INDEX idx_projects_client_id ON projects (client_id)');
        $this->addSql('CREATE INDEX idx_projects_deleted_at ON projects (deleted_at)');
        $this->addSql(
            'CREATE TABLE rates (id UUID NOT NULL, amount NUMERIC(12, 2) NOT NULL, currency VARCHAR(3) NOT NULL, effective_from DATE NOT NULL, role_id UUID DEFAULT NULL, work_id UUID DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))'
        );
        $this->addSql('CREATE INDEX idx_rates_deleted_at ON rates (deleted_at)');
        $this->addSql('CREATE INDEX idx_rates_role_id ON rates (role_id)');
        $this->addSql('CREATE INDEX idx_rates_work_id ON rates (work_id)');
        $this->addSql(
            'CREATE TABLE roles (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))'
        );
        $this->addSql('CREATE INDEX idx_roles_deleted_at ON roles (deleted_at)');
        $this->addSql(
            'CREATE TABLE tasks (id UUID NOT NULL, project_id UUID DEFAULT NULL, cr_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, estimate NUMERIC(8, 2) DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))'
        );
        $this->addSql('CREATE INDEX idx_tasks_project_id ON tasks (project_id)');
        $this->addSql('CREATE INDEX idx_tasks_cr_id ON tasks (cr_id)');
        $this->addSql('CREATE INDEX idx_tasks_deleted_at ON tasks (deleted_at)');
        $this->addSql(
            'CREATE TABLE tickets (id UUID NOT NULL, employee_id UUID NOT NULL, task_id UUID NOT NULL, work_id UUID NOT NULL, date DATE NOT NULL, hours NUMERIC(8, 2) NOT NULL, comment TEXT DEFAULT NULL, rate_snapshot NUMERIC(12, 2) NOT NULL, type VARCHAR(20) NOT NULL, import_source VARCHAR(255) DEFAULT NULL, external_id VARCHAR(255) DEFAULT NULL, is_editable BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))'
        );
        $this->addSql('CREATE INDEX idx_tickets_employee_id ON tickets (employee_id)');
        $this->addSql('CREATE INDEX idx_tickets_task_id ON tickets (task_id)');
        $this->addSql('CREATE INDEX idx_tickets_work_id ON tickets (work_id)');
        $this->addSql('CREATE INDEX idx_tickets_date ON tickets (date)');
        $this->addSql(
            'CREATE UNIQUE INDEX uq_tickets_import_source_external_id ON tickets (import_source, external_id)'
        );
        $this->addSql(
            'CREATE TABLE users (id UUID NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password_hash VARCHAR(255) NOT NULL, system_role VARCHAR(50) NOT NULL, group_id UUID DEFAULT NULL, role_id UUID DEFAULT NULL, is_active BOOLEAN DEFAULT true NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))'
        );
        $this->addSql('CREATE INDEX idx_users_deleted_at ON users (deleted_at)');
        $this->addSql('CREATE INDEX idx_users_group_id ON users (group_id)');
        $this->addSql('CREATE INDEX idx_users_role_id ON users (role_id)');
        $this->addSql('CREATE INDEX idx_users_active_deleted ON users (is_active, deleted_at)');
        $this->addSql('CREATE UNIQUE INDEX uq_users_email ON users (email)');
        $this->addSql(
            'CREATE TABLE works (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))'
        );
        $this->addSql('CREATE INDEX idx_works_deleted_at ON works (deleted_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE change_requests');
        $this->addSql('DROP TABLE clients');
        $this->addSql('DROP TABLE groups');
        $this->addSql('DROP TABLE import_policies');
        $this->addSql('DROP TABLE projects');
        $this->addSql('DROP TABLE rates');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE tasks');
        $this->addSql('DROP TABLE tickets');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE works');
    }
}
