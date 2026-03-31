<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetCustomerListControllerTest extends WebTestCase
{
    public function test_returns_200_with_array(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        // Create at least one customer so the list is non-trivially testable
        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => 'list+' . uniqid() . '@firma.cz',
                'first_name' => 'Seznam',
                'last_name'  => 'Zákazník',
            ]),
        );
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/crm/contacts/customers', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $first = $data[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('email', $first);
        $this->assertArrayHasKey('full_name', $first);
        $this->assertArrayHasKey('registered_at', $first);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/crm/contacts/customers');

        $this->assertResponseStatusCodeSame(401);
    }
}
