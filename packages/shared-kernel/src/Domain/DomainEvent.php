<?php
declare(strict_types=1);

namespace SharedKernel\Domain;

abstract class DomainEvent
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
