<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sales module tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sales_inquiries (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            customer_id VARCHAR(36) DEFAULT NULL,
            customer_name VARCHAR(255) NOT NULL,
            contact_email VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            requested_deadline DATE DEFAULT NULL,
            required_roles JSON NOT NULL DEFAULT \'[]\',
            status VARCHAR(20) NOT NULL DEFAULT \'new\',
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE TABLE sales_inquiry_attachments (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            inquiry_id VARCHAR(36) NOT NULL REFERENCES sales_inquiries(id) ON DELETE CASCADE,
            path VARCHAR(500) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE INDEX idx_sales_inquiry_attachments_inquiry ON sales_inquiry_attachments (inquiry_id)');
        $this->addSql('CREATE TABLE sales_quotes (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            inquiry_id VARCHAR(36) NOT NULL REFERENCES sales_inquiries(id) ON DELETE CASCADE,
            valid_until DATE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'draft\',
            pdf_path VARCHAR(500) DEFAULT NULL,
            notes TEXT NOT NULL DEFAULT \'\',
            total_price_amount INT NOT NULL DEFAULT 0,
            total_price_currency VARCHAR(10) NOT NULL DEFAULT \'CZK\',
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE INDEX idx_sales_quotes_inquiry ON sales_quotes (inquiry_id)');
        $this->addSql('CREATE TABLE sales_quote_phases (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            quote_id VARCHAR(36) NOT NULL REFERENCES sales_quotes(id) ON DELETE CASCADE,
            name VARCHAR(255) NOT NULL,
            required_role VARCHAR(50) NOT NULL,
            duration_days INT NOT NULL,
            daily_rate_amount INT NOT NULL,
            daily_rate_currency VARCHAR(10) NOT NULL DEFAULT \'CZK\',
            subtotal_amount INT NOT NULL,
            subtotal_currency VARCHAR(10) NOT NULL DEFAULT \'CZK\',
            sort_order INT NOT NULL DEFAULT 0
        )');
        $this->addSql('CREATE INDEX idx_sales_quote_phases_quote ON sales_quote_phases (quote_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sales_quote_phases');
        $this->addSql('DROP TABLE sales_quotes');
        $this->addSql('DROP TABLE sales_inquiry_attachments');
        $this->addSql('DROP TABLE sales_inquiries');
    }
}
