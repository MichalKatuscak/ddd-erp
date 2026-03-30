<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Application;

use Crm\Contacts\Application\GetCustomerDetail\CustomerDetailDTO;
use Crm\Contacts\Application\GetCustomerDetail\GetCustomerDetailHandler;
use Crm\Contacts\Application\GetCustomerDetail\GetCustomerDetailQuery;
use Crm\Contacts\Application\GetCustomerList\CustomerListItemDTO;
use Crm\Contacts\Application\GetCustomerList\GetCustomerListHandler;
use Crm\Contacts\Application\GetCustomerList\GetCustomerListQuery;
use Crm\Contacts\Domain\CustomerNotFoundException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetCustomerListHandlerTest extends TestCase
{
    private Connection&MockObject $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
    }

    public function test_returns_list_of_customers(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([
            [
                'id'            => '018e8f2a-1234-7000-8000-000000000001',
                'email'         => 'jan@firma.cz',
                'first_name'    => 'Jan',
                'last_name'     => 'Novák',
                'registered_at' => '2026-01-15 10:00:00',
            ],
        ]);
        $this->connection->method('executeQuery')->willReturn($result);

        $handler = new GetCustomerListHandler($this->connection);
        $items   = ($handler)(new GetCustomerListQuery());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(CustomerListItemDTO::class, $items[0]);
        $this->assertSame('jan@firma.cz', $items[0]->email);
        $this->assertSame('Jan Novák', $items[0]->fullName);
    }

    public function test_returns_customer_detail(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn([
            'id'            => '018e8f2a-1234-7000-8000-000000000001',
            'email'         => 'jan@firma.cz',
            'first_name'    => 'Jan',
            'last_name'     => 'Novák',
            'registered_at' => '2026-01-15 10:00:00',
        ]);
        $this->connection->method('executeQuery')->willReturn($result);

        $handler = new GetCustomerDetailHandler($this->connection);
        $detail  = ($handler)(new GetCustomerDetailQuery('018e8f2a-1234-7000-8000-000000000001'));

        $this->assertInstanceOf(CustomerDetailDTO::class, $detail);
        $this->assertSame('jan@firma.cz', $detail->email);
        $this->assertSame('Jan', $detail->firstName);
    }

    public function test_throws_when_customer_detail_not_found(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn(false);
        $this->connection->method('executeQuery')->willReturn($result);

        $handler = new GetCustomerDetailHandler($this->connection);
        $this->expectException(CustomerNotFoundException::class);
        ($handler)(new GetCustomerDetailQuery('018e8f2a-0000-7000-8000-000000000099'));
    }
}
