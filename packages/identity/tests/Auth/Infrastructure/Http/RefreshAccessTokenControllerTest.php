<?php
declare(strict_types=1);

namespace Identity\Tests\Auth\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RefreshAccessTokenControllerTest extends WebTestCase
{
    public function test_returns_422_on_missing_refresh_token(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/identity/commands/refresh-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('refresh_token', $data['violations']);
    }

    public function test_returns_422_on_invalid_uuid(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/identity/commands/refresh-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refresh_token' => 'not-a-uuid']));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
        $this->assertArrayHasKey('refresh_token', $data['violations']);
    }
}
