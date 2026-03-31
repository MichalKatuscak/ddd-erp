<?php
declare(strict_types=1);

namespace Identity\Tests\Role\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetRoleListControllerTest extends WebTestCase
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

        // Create a role so the list is non-trivially testable
        $client->request('POST', '/api/identity/roles/commands/create-role', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'name'        => 'test-role-' . uniqid(),
            'permissions' => ['crm.contacts.view_customers'],
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/identity/roles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $first = $data[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('permissions', $first);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/identity/roles');

        $this->assertResponseStatusCodeSame(401);
    }
}
