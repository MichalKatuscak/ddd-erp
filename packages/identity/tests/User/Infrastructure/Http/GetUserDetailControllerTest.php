<?php
declare(strict_types=1);

namespace Identity\Tests\User\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetUserDetailControllerTest extends WebTestCase
{
    public function test_returns_200_with_user_data(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        // Create a fresh user to fetch details
        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email'      => 'userdetail+' . uniqid() . '@test.cz',
            'password'   => 'password123',
            'first_name' => 'Detail',
            'last_name'  => 'User',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('GET', '/api/identity/users/' . $id, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($id, $data['id']);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('first_name', $data);
        $this->assertArrayHasKey('last_name', $data);
        $this->assertArrayHasKey('role_ids', $data);
        $this->assertArrayHasKey('active', $data);
        $this->assertArrayHasKey('created_at', $data);
    }

    public function test_returns_404_for_non_existent_user(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('GET', '/api/identity/users/00000000-0000-7000-8000-000000000001', [], [], [
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

        $client->request('GET', '/api/identity/users/00000000-0000-7000-8000-000000000001');

        $this->assertResponseStatusCodeSame(401);
    }
}
