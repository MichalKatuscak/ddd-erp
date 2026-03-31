<?php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

interface CustomerRepository
{
    /** @throws CustomerNotFoundException */
    public function get(CustomerId $id): Customer;

    public function findByEmail(CustomerEmail $email): ?Customer;

    public function save(Customer $customer): void;

    public function nextIdentity(): CustomerId;
}
