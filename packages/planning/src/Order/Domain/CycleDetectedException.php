<?php
declare(strict_types=1);

namespace Planning\Order\Domain;

final class CycleDetectedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Phase dependencies form a cycle');
    }
}
