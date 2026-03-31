<?php
declare(strict_types=1);

namespace Identity\Tests\Role\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetRoleDetailControllerTest extends WebTestCase
{
    public function test_returns_200_with_role_data(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        // Create a role to fetch
        $client->request('POST', '/api/identity/roles/commands/create-role', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'name'        => 'detail-role-' . uniqid(),
            'permissions' => ['crm.contacts.view_customers', 'identity.users.view_users'],
        ]));
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('GET', '/api/identity/roles/' . $id, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($id, $data['id']);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('permissions', $data);
        $this->assertIsArray($data['permissions']);
    }

    public function test_returns_404_for_non_existent_role(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('GET', '/api/identity/roles/00000000-0000-7000-8000-000000000001', [], [], [
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

        $client->request('GET', '/api/identity/roles/00000000-0000-7000-8000-000000000001');

        $this->assertResponseStatusCodeSame(401);
    }
}
