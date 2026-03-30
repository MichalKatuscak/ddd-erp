<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Application;

use SharedKernel\Application\EventBusInterface;
use SharedKernel\Domain\DomainEvent;

final class SpyEventBus implements EventBusInterface
{
    /** @var DomainEvent[] */
    public array $dispatched = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->dispatched[] = $event;
    }
}
