<?php
declare(strict_types=1);

namespace Audit\Tests\Infrastructure;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuditLogIntegrationTest extends WebTestCase
{
    public function test_register_customer_creates_audit_log_entry(): void
    {
        $client = static::createClient();

        // Login to get JWT token
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $email = 'audit-test-' . uniqid() . '@firma.cz';

        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => $email,
                'first_name' => 'Audit',
                'last_name'  => 'Test',
            ]),
        );

        $this->assertResponseStatusCodeSame(201);

        // Query audit_log table directly
        $conn = static::getContainer()->get('doctrine.dbal.default_connection');
        $rows = $conn->fetchAllAssociative(
            "SELECT * FROM audit_log WHERE event_type = 'CustomerRegistered' AND payload->>'email' = :email ORDER BY occurred_at DESC LIMIT 1",
            ['email' => $email],
        );

        $this->assertCount(1, $rows, 'Expected one audit_log entry for CustomerRegistered');
        $this->assertSame('CustomerRegistered', $rows[0]['event_type']);
        $this->assertNotNull($rows[0]['aggregate_id']);
        $this->assertNotNull($rows[0]['performed_by'], 'performed_by should be set for authenticated request');

        $payload = json_decode($rows[0]['payload'], true);
        $this->assertSame($email, $payload['email']);
        $this->assertSame('Audit', $payload['first_name']);
        $this->assertSame('Test', $payload['last_name']);
    }
}
