<?php
declare(strict_types=1);

namespace Planning\Order\Domain;

final class OrderNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Order not found: '$id'");
    }
}
