<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetCustomerDetailControllerTest extends WebTestCase
{
    public function test_returns_200_with_customer_data(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        // Create a customer to fetch
        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => 'detail+' . uniqid() . '@firma.cz',
                'first_name' => 'Detail',
                'last_name'  => 'Test',
            ]),
        );
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('GET', '/api/crm/contacts/customers/' . $id, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($id, $data['id']);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('first_name', $data);
        $this->assertArrayHasKey('last_name', $data);
        $this->assertArrayHasKey('registered_at', $data);
    }

    public function test_returns_404_for_non_existent_customer(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('GET', '/api/crm/contacts/customers/00000000-0000-7000-8000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/not-found', $data['type']);
        $this->assertSame(404, $data['status']);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/crm/contacts/customers/00000000-0000-7000-8000-000000000001');

        $this->assertResponseStatusCodeSame(401);
    }
}
