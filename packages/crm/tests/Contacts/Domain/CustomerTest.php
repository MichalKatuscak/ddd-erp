<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Domain;

use Crm\Contacts\Domain\Customer;
use Crm\Contacts\Domain\CustomerEmail;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerName;
use Crm\Contacts\Domain\CustomerRegistered;
use Crm\Contacts\Domain\CustomerUpdated;
use PHPUnit\Framework\TestCase;

final class CustomerTest extends TestCase
{
    private CustomerId $id;
    private CustomerEmail $email;
    private CustomerName $name;

    protected function setUp(): void
    {
        $this->id    = CustomerId::fromString('018e8f2a-1234-7000-8000-000000000001');
        $this->email = CustomerEmail::fromString('jan@firma.cz');
        $this->name  = CustomerName::fromParts('Jan', 'Novák');
    }

    public function test_registers_customer(): void
    {
        $customer = Customer::register($this->id, $this->email, $this->name);
        $this->assertTrue($customer->id()->equals($this->id));
        $this->assertTrue($customer->email()->equals($this->email));
        $this->assertTrue($customer->name()->equals($this->name));
    }

    public function test_registration_emits_customer_registered_event(): void
    {
        $customer = Customer::register($this->id, $this->email, $this->name);
        $events   = $customer->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CustomerRegistered::class, $events[0]);
        $this->assertTrue($events[0]->customerId->equals($this->id));
    }

    public function test_pull_clears_events(): void
    {
        $customer = Customer::register($this->id, $this->email, $this->name);
        $customer->pullDomainEvents();
        $this->assertCount(0, $customer->pullDomainEvents());
    }

    public function test_updates_customer(): void
    {
        $customer = Customer::register($this->id, $this->email, $this->name);
        $customer->pullDomainEvents();

        $newEmail = CustomerEmail::fromString('petr@firma.cz');
        $newName  = CustomerName::fromParts('Petr', 'Svoboda');
        $customer->update($newEmail, $newName);

        $this->assertTrue($customer->email()->equals($newEmail));
        $this->assertTrue($customer->name()->equals($newName));

        $events = $customer->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CustomerUpdated::class, $events[0]);
    }
}
