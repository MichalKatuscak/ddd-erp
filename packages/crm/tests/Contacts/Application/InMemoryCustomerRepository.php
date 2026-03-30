<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Application;

use Crm\Contacts\Domain\Customer;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerNotFoundException;
use Crm\Contacts\Domain\CustomerRepository;

final class InMemoryCustomerRepository implements CustomerRepository
{
    /** @var Customer[] */
    private array $customers = [];

    public function get(CustomerId $id): Customer
    {
        return $this->customers[$id->value()]
            ?? throw new CustomerNotFoundException($id->value());
    }

    public function save(Customer $customer): void
    {
        $this->customers[$customer->id()->value()] = $customer;
    }

    public function nextIdentity(): CustomerId
    {
        return CustomerId::generate();
    }
}
