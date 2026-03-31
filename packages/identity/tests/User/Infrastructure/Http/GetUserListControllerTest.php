<?php
declare(strict_types=1);

namespace Identity\Tests\User\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetUserListControllerTest extends WebTestCase
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

        $client->request('GET', '/api/identity/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data); // seeded admin always present
        $first = $data[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('email', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('role_ids', $first);
        $this->assertArrayHasKey('active', $first);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/identity/users');

        $this->assertResponseStatusCodeSame(401);
    }
}
