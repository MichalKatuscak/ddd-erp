<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331133904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create planning tables (orders, phases, workers, allocations) and seed super-admin permissions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE planning_orders (
            id VARCHAR NOT NULL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            client_name VARCHAR(255) NOT NULL,
            planned_start_date DATE NOT NULL,
            status VARCHAR(50) NOT NULL
        )');

        $this->addSql('CREATE TABLE planning_phases (
            id VARCHAR NOT NULL PRIMARY KEY,
            order_id VARCHAR NOT NULL REFERENCES planning_orders(id) ON DELETE CASCADE,
            name VARCHAR(255) NOT NULL,
            required_role VARCHAR(50) NOT NULL,
            required_skills JSON NOT NULL DEFAULT \'[]\',
            headcount INT NOT NULL DEFAULT 1,
            duration_days INT NOT NULL,
            depends_on JSON NOT NULL DEFAULT \'[]\',
            start_date DATE DEFAULT NULL,
            end_date DATE DEFAULT NULL,
            assignments JSON NOT NULL DEFAULT \'[]\'
        )');

        $this->addSql('CREATE INDEX idx_planning_phases_order_id ON planning_phases (order_id)');

        $this->addSql('CREATE TABLE planning_workers (
            id VARCHAR NOT NULL PRIMARY KEY,
            primary_role VARCHAR(50) NOT NULL,
            skills JSON NOT NULL DEFAULT \'[]\'
        )');

        $this->addSql('CREATE TABLE planning_worker_allocations (
            id VARCHAR NOT NULL PRIMARY KEY,
            worker_id VARCHAR NOT NULL REFERENCES planning_workers(id) ON DELETE CASCADE,
            order_id VARCHAR NOT NULL,
            phase_id VARCHAR NOT NULL,
            allocation_percent INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL
        )');

        $this->addSql('CREATE INDEX idx_pwa_worker_id ON planning_worker_allocations (worker_id)');
        $this->addSql('CREATE INDEX idx_pwa_dates ON planning_worker_allocations (start_date, end_date)');

        $this->addSql("UPDATE identity_roles
            SET permissions = (permissions::jsonb || '[\"planning.orders.manage\",\"planning.workers.manage\"]'::jsonb)::json
            WHERE name = 'super-admin'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE planning_worker_allocations');
        $this->addSql('DROP TABLE planning_workers');
        $this->addSql('DROP TABLE planning_phases');
        $this->addSql('DROP TABLE planning_orders');

        $this->addSql("UPDATE identity_roles
            SET permissions = (
                SELECT COALESCE(json_agg(p), '[]'::json)
                FROM json_array_elements_text(permissions::jsonb) AS t(p)
                WHERE p NOT IN ('planning.orders.manage', 'planning.workers.manage')
            )
            WHERE name = 'super-admin'");
    }
}
