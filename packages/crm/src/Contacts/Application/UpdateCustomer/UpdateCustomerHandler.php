<?php
declare(strict_types=1);

namespace Crm\Contacts\Application\UpdateCustomer;

use Crm\Contacts\Domain\CustomerEmail;
use Crm\Contacts\Domain\CustomerId;
use Crm\Contacts\Domain\CustomerName;
use Crm\Contacts\Domain\CustomerRepository;
use SharedKernel\Application\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateCustomerHandler
{
    public function __construct(
        private readonly CustomerRepository $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateCustomerCommand $command): void
    {
        $customer = $this->repository->get(CustomerId::fromString($command->customerId));

        $customer->update(
            CustomerEmail::fromString($command->email),
            CustomerName::fromParts($command->firstName, $command->lastName),
        );

        $this->repository->save($customer);

        foreach ($customer->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
