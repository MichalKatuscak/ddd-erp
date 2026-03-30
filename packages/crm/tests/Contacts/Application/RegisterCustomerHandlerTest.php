<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Application;

use Crm\Contacts\Application\RegisterCustomer\RegisterCustomerCommand;
use Crm\Contacts\Application\RegisterCustomer\RegisterCustomerHandler;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerRegistered;
use PHPUnit\Framework\TestCase;

final class RegisterCustomerHandlerTest extends TestCase
{
    private InMemoryCustomerRepository $repository;
    private SpyEventBus $eventBus;
    private RegisterCustomerHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCustomerRepository();
        $this->eventBus   = new SpyEventBus();
        $this->handler    = new RegisterCustomerHandler($this->repository, $this->eventBus);
    }

    public function test_registers_customer_and_persists(): void
    {
        $customerId = CustomerId::generate()->value();
        ($this->handler)(new RegisterCustomerCommand(
            customerId: $customerId,
            email: 'jan@firma.cz',
            firstName: 'Jan',
            lastName: 'Novák',
        ));

        $customer = $this->repository->get(CustomerId::fromString($customerId));
        $this->assertSame('jan@firma.cz', $customer->email()->value());
        $this->assertSame('Jan', $customer->name()->firstName());
    }

    public function test_dispatches_customer_registered_event(): void
    {
        ($this->handler)(new RegisterCustomerCommand(
            customerId: CustomerId::generate()->value(),
            email: 'jan@firma.cz',
            firstName: 'Jan',
            lastName: 'Novák',
        ));

        $this->assertCount(1, $this->eventBus->dispatched);
        $this->assertInstanceOf(CustomerRegistered::class, $this->eventBus->dispatched[0]);
    }
}
