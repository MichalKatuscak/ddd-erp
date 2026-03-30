<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Application;

use Crm\Contacts\Application\RegisterCustomer\RegisterCustomerCommand;
use Crm\Contacts\Application\RegisterCustomer\RegisterCustomerHandler;
use Crm\Contacts\Application\UpdateCustomer\UpdateCustomerCommand;
use Crm\Contacts\Application\UpdateCustomer\UpdateCustomerHandler;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerNotFoundException;
use Crm\Contacts\Domain\CustomerUpdated;
use PHPUnit\Framework\TestCase;

final class UpdateCustomerHandlerTest extends TestCase
{
    private InMemoryCustomerRepository $repository;
    private SpyEventBus $eventBus;
    private string $existingCustomerId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCustomerRepository();
        $this->eventBus   = new SpyEventBus();

        $this->existingCustomerId = CustomerId::generate()->value();
        $registerHandler = new RegisterCustomerHandler($this->repository, $this->eventBus);
        ($registerHandler)(new RegisterCustomerCommand(
            customerId: $this->existingCustomerId,
            email: 'jan@firma.cz',
            firstName: 'Jan',
            lastName: 'Novák',
        ));
        $this->eventBus->dispatched = [];
    }

    public function test_updates_customer_email_and_name(): void
    {
        $handler = new UpdateCustomerHandler($this->repository, $this->eventBus);
        ($handler)(new UpdateCustomerCommand(
            customerId: $this->existingCustomerId,
            email: 'petr@firma.cz',
            firstName: 'Petr',
            lastName: 'Svoboda',
        ));

        $customer = $this->repository->get(CustomerId::fromString($this->existingCustomerId));
        $this->assertSame('petr@firma.cz', $customer->email()->value());
        $this->assertSame('Petr', $customer->name()->firstName());
    }

    public function test_dispatches_customer_updated_event(): void
    {
        $handler = new UpdateCustomerHandler($this->repository, $this->eventBus);
        ($handler)(new UpdateCustomerCommand(
            customerId: $this->existingCustomerId,
            email: 'petr@firma.cz',
            firstName: 'Petr',
            lastName: 'Svoboda',
        ));

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(CustomerUpdated::class, $this->eventBus->dispatched[0]);
    }

    public function test_throws_when_customer_not_found(): void
    {
        $handler = new UpdateCustomerHandler($this->repository, $this->eventBus);
        $this->expectException(CustomerNotFoundException::class);
        ($handler)(new UpdateCustomerCommand(
            customerId: '018e8f2a-0000-7000-8000-000000000099',
            email: 'x@x.cz',
            firstName: 'X',
            lastName: 'Y',
        ));
    }
}
