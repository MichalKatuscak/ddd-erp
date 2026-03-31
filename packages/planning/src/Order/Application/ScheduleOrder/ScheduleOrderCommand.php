<?php
declare(strict_types=1);

namespace Planning\Order\Application\ScheduleOrder;

final readonly class ScheduleOrderCommand
{
    public function __construct(public string $orderId) {}
}
