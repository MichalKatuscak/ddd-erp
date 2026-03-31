<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331214738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create audit_log table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_log (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            event_type VARCHAR(100) NOT NULL,
            aggregate_id VARCHAR(36) NOT NULL,
            payload JSON NOT NULL,
            performed_by VARCHAR(36) DEFAULT NULL,
            occurred_at TIMESTAMPTZ NOT NULL
        )');
        $this->addSql('CREATE INDEX idx_audit_log_event_type ON audit_log (event_type)');
        $this->addSql('CREATE INDEX idx_audit_log_aggregate_id ON audit_log (aggregate_id)');
        $this->addSql('CREATE INDEX idx_audit_log_occurred_at ON audit_log (occurred_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_log');
    }
}
