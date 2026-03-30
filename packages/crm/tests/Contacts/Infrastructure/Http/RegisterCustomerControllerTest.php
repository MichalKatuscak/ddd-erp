<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegisterCustomerControllerTest extends WebTestCase
{
    public function test_registers_customer_and_returns_201(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('admin:password'),
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => 'jan+' . uniqid() . '@firma.cz',
                'first_name' => 'Jan',
                'last_name'  => 'Novák',
            ]),
        );

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertNotEmpty($data['id']);
    }

    public function test_returns_422_on_invalid_email(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'POST',
            uri: '/api/crm/contacts/commands/register-customer',
            server: [
                'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('admin:password'),
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: json_encode([
                'email'      => 'not-an-email',
                'first_name' => 'Jan',
                'last_name'  => 'Novák',
            ]),
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function test_returns_401_without_auth(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/crm/contacts/commands/register-customer');

        $this->assertResponseStatusCodeSame(401);
    }
}
