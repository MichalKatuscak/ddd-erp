<?php
declare(strict_types=1);
namespace Sales\Tests\Inquiry\Application;
use SharedKernel\Application\CommandBusInterface;
final class SpyCommandBus implements CommandBusInterface
{
    public array $dispatched = [];
    public function dispatch(object $command): void { $this->dispatched[] = $command; }
}
