<?php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

use SharedKernel\Domain\AggregateRoot;

final class Customer extends AggregateRoot
{
    private function __construct(
        private readonly CustomerId $id,
        private CustomerEmail $email,
        private CustomerName $name,
        private readonly \DateTimeImmutable $registeredAt,
    ) {}

    public static function register(
        CustomerId $id,
        CustomerEmail $email,
        CustomerName $name,
    ): self {
        $customer = new self($id, $email, $name, new \DateTimeImmutable());
        $customer->recordEvent(new CustomerRegistered($id, $email, $name));
        return $customer;
    }

    public function update(CustomerEmail $email, CustomerName $name): void
    {
        $this->email = $email;
        $this->name  = $name;
        $this->recordEvent(new CustomerUpdated($this->id, $email, $name));
    }

    public function id(): CustomerId { return $this->id; }
    public function email(): CustomerEmail { return $this->email; }
    public function name(): CustomerName { return $this->name; }
    public function registeredAt(): \DateTimeImmutable { return $this->registeredAt; }
}
