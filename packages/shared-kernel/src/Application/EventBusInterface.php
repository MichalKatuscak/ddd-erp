<?php
declare(strict_types=1);

namespace SharedKernel\Application;

use SharedKernel\Domain\DomainEvent;

interface EventBusInterface
{
    public function dispatch(DomainEvent $event): void;
}
