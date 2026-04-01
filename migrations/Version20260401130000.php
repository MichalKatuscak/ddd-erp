<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sales permissions to super-admin role';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE identity_roles
            SET permissions = (permissions::jsonb || '[\"sales.inquiries.manage\",\"sales.quotes.manage\"]'::jsonb)::json
            WHERE name = 'super-admin'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE identity_roles
            SET permissions = (
                SELECT COALESCE(json_agg(p), '[]'::json)
                FROM json_array_elements_text(permissions::jsonb) AS t(p)
                WHERE p NOT IN ('sales.inquiries.manage', 'sales.quotes.manage')
            )
            WHERE name = 'super-admin'");
    }
}
