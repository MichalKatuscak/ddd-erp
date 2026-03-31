<?php
declare(strict_types=1);

namespace Identity\Tests\User\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DeactivateUserControllerTest extends WebTestCase
{
    public function test_deactivates_user_and_returns_204(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        // Create a fresh user to deactivate (do NOT deactivate the seeded admin)
        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email'      => 'todeactivate+' . uniqid() . '@test.cz',
            'password'   => 'password123',
            'first_name' => 'To',
            'last_name'  => 'Deactivate',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $userId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request(
            method: 'POST',
            uri: '/api/identity/users/commands/deactivate-user/' . $userId,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
        );

        $this->assertResponseStatusCodeSame(204);
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

        $client->request(
            method: 'POST',
            uri: '/api/identity/users/commands/deactivate-user/00000000-0000-7000-8000-000000000001',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
        );

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/not-found', $data['type']);
        $this->assertSame(404, $data['status']);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/users/commands/deactivate-user/00000000-0000-7000-8000-000000000001');

        $this->assertResponseStatusCodeSame(401);
    }
}
