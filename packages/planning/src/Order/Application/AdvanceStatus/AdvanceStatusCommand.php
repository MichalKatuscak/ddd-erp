<?php
declare(strict_types=1);

namespace Planning\Order\Application\AdvanceStatus;

final readonly class AdvanceStatusCommand
{
    public function __construct(public string $orderId) {}
}
