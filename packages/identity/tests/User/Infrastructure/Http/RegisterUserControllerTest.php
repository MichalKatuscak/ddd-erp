<?php
declare(strict_types=1);

namespace Identity\Tests\User\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegisterUserControllerTest extends WebTestCase
{
    public function test_returns_422_on_invalid_email(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email' => 'not-an-email', 'password' => 'password123',
            'first_name' => 'Jan', 'last_name' => 'Test',
        ]));

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

        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/validation', $data['type']);
    }

    public function test_registers_user_and_returns_201(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email'      => 'newuser+' . uniqid() . '@test.cz',
            'password'   => 'password123',
            'first_name' => 'Nový',
            'last_name'  => 'Uživatel',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
    }

    public function test_returns_422_on_duplicate_email(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@erp.local', 'password' => 'changeme']));
        $token = json_decode($client->getResponse()->getContent(), true)['access_token'];

        $email = 'dupuser+' . uniqid() . '@test.cz';

        // Register once — should succeed
        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email'      => $email,
            'password'   => 'password123',
            'first_name' => 'Jan',
            'last_name'  => 'Test',
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Register again with same email — should fail with 422 domain error
        $client->request('POST', '/api/identity/users/commands/register-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE'       => 'application/json',
        ], json_encode([
            'email'      => $email,
            'password'   => 'password123',
            'first_name' => 'Petr',
            'last_name'  => 'Dvořák',
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('/errors/domain', $data['type']);
        $this->assertSame(422, $data['status']);
    }
}
