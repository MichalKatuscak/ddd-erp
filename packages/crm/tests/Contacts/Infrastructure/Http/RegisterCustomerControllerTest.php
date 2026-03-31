<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegisterCustomerControllerTest extends WebTestCase
{
    public function test_registers_customer_and_returns_201(): void
    {
        $client = static::createClient();

        // Login to get JWT token
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $loginData = json_decode($client->getResponse()->getContent(), true);
        $token = $loginData['access_token'];

        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => 'jan+' . uniqid() . '@firma.cz',
                'first_name' => 'Jan',
                'last_name'  => 'Novák',
            ]),
        );

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertNotEmpty($data['id']);
    }

    public function test_returns_422_on_invalid_email(): void
    {
        $client = static::createClient();

        // Login to get JWT token
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $loginData = json_decode($client->getResponse()->getContent(), true);
        $token = $loginData['access_token'];

        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => 'not-an-email',
                'first_name' => 'Jan',
                'last_name'  => 'Novák',
            ]),
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('violations', $data);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/crm/contacts/commands/register-customer');

        $this->assertResponseStatusCodeSame(401);
    }

    public function test_returns_422_on_duplicate_email(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $email = 'duplicate+' . uniqid() . '@firma.cz';

        // Register once — should succeed
        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => $email,
                'first_name' => 'Jan',
                'last_name'  => 'Novák',
            ]),
        );
        $this->assertResponseStatusCodeSame(201);

        // Register again with same email — should fail with 422 domain error
        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => $email,
                'first_name' => 'Petr',
                'last_name'  => 'Dvořák',
            ]),
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/domain', $data['type']);
        $this->assertSame(422, $data['status']);
    }
}
