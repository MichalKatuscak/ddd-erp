<?php
// tests/Identity/E2e/AuthFlowTest.php
declare(strict_types=1);

namespace App\Tests\Identity\E2e;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthFlowTest extends WebTestCase
{
    public function test_login_returns_tokens(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertSame(900, $data['expires_in']);
    }

    public function test_login_with_wrong_password_returns_error(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'wrong-password',
        ]));

        $this->assertResponseStatusCodeSame(500); // DomainException handler
    }

    public function test_jwt_grants_access_to_crm_endpoint(): void
    {
        $client = static::createClient();

        // Login
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $loginData = json_decode($client->getResponse()->getContent(), true);
        $accessToken = $loginData['access_token'];

        // Access CRM endpoint with JWT
        $client->request('GET', '/api/crm/contacts/customers', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function test_missing_token_returns_401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/crm/contacts/customers');

        $this->assertResponseStatusCodeSame(401);
    }

    public function test_invalid_token_returns_401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/crm/contacts/customers', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid.token.here',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function test_refresh_token_rotation(): void
    {
        $client = static::createClient();

        // Login
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $loginData = json_decode($client->getResponse()->getContent(), true);
        $refreshToken = $loginData['refresh_token'];

        // Refresh
        $client->request('POST', '/api/identity/commands/refresh-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'refresh_token' => $refreshToken,
        ]));

        $this->assertResponseIsSuccessful();
        $refreshData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $refreshData);
        $this->assertArrayHasKey('refresh_token', $refreshData);

        // New refresh token is different (rotation)
        $this->assertNotSame($refreshToken, $refreshData['refresh_token']);

        // Old refresh token should no longer work
        $client->request('POST', '/api/identity/commands/refresh-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'refresh_token' => $refreshToken,
        ]));

        // Should fail (expired/revoked token)
        $this->assertResponseStatusCodeSame(500);
    }

    public function test_logout_revokes_refresh_token(): void
    {
        $client = static::createClient();

        // Login
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $loginData = json_decode($client->getResponse()->getContent(), true);
        $accessToken = $loginData['access_token'];
        $refreshToken = $loginData['refresh_token'];

        // Logout
        $client->request('POST', '/api/identity/commands/logout', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
        ], json_encode([
            'refresh_token' => $refreshToken,
        ]));

        $this->assertResponseStatusCodeSame(204);

        // Refresh with revoked token should fail
        $client->request('POST', '/api/identity/commands/refresh-token', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'refresh_token' => $refreshToken,
        ]));

        $this->assertResponseStatusCodeSame(500);
    }

    public function test_get_current_user(): void
    {
        $client = static::createClient();

        // Login
        $client->request('POST', '/api/identity/commands/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'admin@erp.local',
            'password' => 'changeme',
        ]));
        $loginData = json_decode($client->getResponse()->getContent(), true);
        $accessToken = $loginData['access_token'];

        // Get current user
        $client->request('GET', '/api/identity/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('admin@erp.local', $data['email']);
        $this->assertSame('Admin', $data['first_name']);
        $this->assertSame('ERP', $data['last_name']);
        $this->assertContains('crm.contacts.view_customers', $data['permissions']);
        $this->assertContains('identity.users.manage', $data['permissions']);
    }
}
