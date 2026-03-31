<?php
declare(strict_types=1);

namespace Identity\Tests\Role\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateRoleControllerTest extends WebTestCase
{
    public function test_returns_422_on_missing_name(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('POST', '/api/identity/roles/commands/create-role', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode(['permissions' => ['crm.contacts.view_customers']]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('name', $data['violations']);
    }

    public function test_returns_422_on_empty_permissions(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('POST', '/api/identity/roles/commands/create-role', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode(['name' => 'Test Role', 'permissions' => []]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('permissions', $data['violations']);
    }

    public function test_creates_role_and_returns_201(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('POST', '/api/identity/roles/commands/create-role', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'name'        => 'test-role-' . uniqid(),
            'permissions' => ['crm.contacts.view_customers'],
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
    }
}
