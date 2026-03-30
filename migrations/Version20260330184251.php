<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260330184251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE identity_refresh_tokens (user_id VARCHAR NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id VARCHAR NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE identity_roles (name VARCHAR(100) NOT NULL, permissions JSON NOT NULL, id VARCHAR NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5C6564375E237E06 ON identity_roles (name)');
        $this->addSql('CREATE TABLE identity_users (email VARCHAR(255) NOT NULL, password_hash VARCHAR(255) NOT NULL, role_ids JSON NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id VARCHAR NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FED8EF19E7927C74 ON identity_users (email)');

        // Seed: super-admin role with all permissions
        $roleId = '019577a0-0000-7000-8000-000000000001';
        $this->addSql("INSERT INTO identity_roles (id, name, permissions) VALUES (:id, :name, :permissions)", [
            'id'          => $roleId,
            'name'        => 'super-admin',
            'permissions' => json_encode([
                'crm.contacts.view_customers',
                'crm.contacts.create_customer',
                'crm.contacts.update_customer',
                'identity.users.manage',
                'identity.users.view',
                'identity.roles.manage',
            ]),
        ]);

        // Seed: admin user with password 'changeme'
        $passwordHash = password_hash('changeme', PASSWORD_BCRYPT);
        $userId = '019577a0-0000-7000-8000-000000000002';
        $this->addSql("INSERT INTO identity_users (id, email, password_hash, first_name, last_name, role_ids, active, created_at) VALUES (:id, :email, :password, :first_name, :last_name, :role_ids, :active, :created_at)", [
            'id'         => $userId,
            'email'      => 'admin@erp.local',
            'password'   => $passwordHash,
            'first_name' => 'Admin',
            'last_name'  => 'ERP',
            'role_ids'   => json_encode([$roleId]),
            'active'     => true,
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE identity_refresh_tokens');
        $this->addSql('DROP TABLE identity_roles');
        $this->addSql('DROP TABLE identity_users');
    }
}
