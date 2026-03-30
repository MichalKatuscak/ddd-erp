<?php
declare(strict_types=1);

namespace SharedKernel\Application;

interface CommandBusInterface
{
    public function dispatch(object $command): void;
}
