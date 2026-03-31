<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UpdateCustomerControllerTest extends WebTestCase
{
    public function test_returns_422_on_invalid_email(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('POST', '/api/crm/contacts/commands/register-customer', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'update+' . uniqid() . '@test.cz', 'first_name' => 'Jan', 'last_name' => 'Test'])
        );
        $id = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('PUT', '/api/crm/contacts/commands/update-customer/' . $id, [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'not-an-email', 'first_name' => 'Jan', 'last_name' => 'Test'])
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('email', $data['violations']);
    }

    public function test_returns_422_on_missing_fields(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('PUT', '/api/crm/contacts/commands/update-customer/00000000-0000-0000-0000-000000000001', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
    }

    public function test_returns_404_for_non_existent_customer(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('PUT', '/api/crm/contacts/commands/update-customer/00000000-0000-7000-8000-000000000001', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'new@test.cz', 'first_name' => 'Jan', 'last_name' => 'Test'])
        );

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/not-found', $data['type']);
        $this->assertSame(404, $data['status']);
    }
}
