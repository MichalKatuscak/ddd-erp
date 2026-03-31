<?php
declare(strict_types=1);

namespace Planning\Order\Application\GetOrderList;

final readonly class GetOrderListQuery
{
    public function __construct(
        public int $limit = 50,
        public int $offset = 0,
    ) {}
}
