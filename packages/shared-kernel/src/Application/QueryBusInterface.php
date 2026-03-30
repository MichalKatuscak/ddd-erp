<?php
declare(strict_types=1);

namespace SharedKernel\Application;

interface QueryBusInterface
{
    public function dispatch(object $query): mixed;
}
