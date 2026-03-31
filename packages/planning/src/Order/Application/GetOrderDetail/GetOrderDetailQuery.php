<?php
declare(strict_types=1);

namespace Planning\Order\Application\GetOrderDetail;

final readonly class GetOrderDetailQuery
{
    public function __construct(public string $orderId) {}
}
