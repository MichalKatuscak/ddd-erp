<?php
declare(strict_types=1);

namespace Identity\Tests\User\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AssignRolesToUserControllerTest extends WebTestCase
{
    public function test_returns_422_when_role_ids_is_missing(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('POST', '/api/identity/users/commands/assign-roles/00000000-0000-0000-0000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('role_ids', $data['violations']);
    }

    public function test_returns_422_when_role_ids_contains_invalid_uuid(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('POST', '/api/identity/users/commands/assign-roles/00000000-0000-0000-0000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode(['role_ids' => ['not-a-uuid']]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
    }
}
