<?php
declare(strict_types=1);

namespace Identity\Tests\User\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UpdateUserControllerTest extends WebTestCase
{
    public function test_returns_422_on_invalid_email(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('PUT', '/api/identity/users/commands/update-user/00000000-0000-0000-0000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode(['email' => 'not-an-email', 'first_name' => 'Jan', 'last_name' => 'Test']));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('email', $data['violations']);
    }

    public function test_returns_422_on_missing_fields(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('PUT', '/api/identity/users/commands/update-user/00000000-0000-0000-0000-000000000001', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
    }
}
