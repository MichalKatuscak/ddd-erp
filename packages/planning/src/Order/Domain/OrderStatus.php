<?php
declare(strict_types=1);

namespace Planning\Order\Domain;

enum OrderStatus: string
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Shipped = 'shipped';

    public function next(): self
    {
        return match($this) {
            self::New       => self::Confirmed,
            self::Confirmed => self::InProgress,
            self::InProgress => self::Completed,
            self::Completed => self::Shipped,
            self::Shipped   => throw new InvalidStatusTransitionException('Order is already shipped'),
        };
    }
}
