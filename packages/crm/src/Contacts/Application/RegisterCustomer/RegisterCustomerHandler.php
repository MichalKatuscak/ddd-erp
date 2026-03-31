<?php
declare(strict_types=1);

namespace Crm\Contacts\Application\RegisterCustomer;

use Crm\Contacts\Domain\Customer;
use Crm\Contacts\Domain\CustomerEmail;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerName;
use Crm\Contacts\Domain\CustomerRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RegisterCustomerHandler
{
    public function __construct(
        private readonly CustomerRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(RegisterCustomerCommand $command): void
    {
        $email = CustomerEmail::fromString($command->email);
        if ($this->repository->findByEmail($email) !== null) {
            throw new \DomainException("Customer with email '{$command->email}' is already registered");
        }

        $customer = Customer::register(
            CustomerId::fromString($command->customerId),
            $email,
            CustomerName::fromParts($command->firstName, $command->lastName),
        );

        $this->repository->save($customer);

        foreach ($customer->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
