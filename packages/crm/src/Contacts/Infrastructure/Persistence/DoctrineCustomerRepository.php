<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Persistence;

use Crm\Contacts\Domain\Customer;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerNotFoundException;
use Crm\Contacts\Domain\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineCustomerRepository implements CustomerRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function get(CustomerId $id): Customer
    {
        $customer = $this->entityManager->find(Customer::class, $id);
        if ($customer === null) {
            throw new CustomerNotFoundException($id->value());
        }
        return $customer;
    }

    public function save(Customer $customer): void
    {
        $this->entityManager->persist($customer);
        $this->entityManager->flush();
    }

    public function nextIdentity(): CustomerId
    {
        return CustomerId::generate();
    }
}
