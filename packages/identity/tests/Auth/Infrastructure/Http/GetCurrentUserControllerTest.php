<?php
declare(strict_types=1);

namespace Identity\Tests\Auth\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetCurrentUserControllerTest extends WebTestCase
{
    public function test_returns_200_with_current_user_data(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('GET', '/api/identity/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertSame('admin@erp.local', $data['email']);
        $this->assertArrayHasKey('first_name', $data);
        $this->assertArrayHasKey('last_name', $data);
        $this->assertArrayHasKey('permissions', $data);
        $this->assertIsArray($data['permissions']);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/identity/me');

        $this->assertResponseStatusCodeSame(401);
    }
}
