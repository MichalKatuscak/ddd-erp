<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use SharedKernel\Application\EventBusInterface;
use SharedKernel\Domain\DomainEvent;
final class SpyEventBus implements EventBusInterface
{
    public array $dispatched = [];
    public function dispatch(DomainEvent $event): void { $this->dispatched[] = $event; }
}
