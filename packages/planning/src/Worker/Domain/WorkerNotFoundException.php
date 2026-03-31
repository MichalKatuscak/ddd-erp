<?php
declare(strict_types=1);

namespace Planning\Worker\Domain;

final class WorkerNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Worker not found: '$id'");
    }
}
