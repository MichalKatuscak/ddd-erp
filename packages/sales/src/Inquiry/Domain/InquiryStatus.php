<?php
declare(strict_types=1);

namespace Sales\Inquiry\Domain;

enum InquiryStatus: string
{
    case New        = 'new';
    case InProgress = 'in_progress';
    case Quoted     = 'quoted';
    case Won        = 'won';
    case Lost       = 'lost';
    case Cancelled  = 'cancelled';

    public function next(): self
    {
        return match($this) {
            self::New        => self::InProgress,
            self::InProgress => self::Quoted,
            default          => throw new InvalidStatusTransitionException(
                "Cannot advance from terminal or branching status '{$this->value}'"
            ),
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match($this) {
            self::New        => in_array($target, [self::InProgress, self::Cancelled], true),
            self::InProgress => in_array($target, [self::Quoted, self::Cancelled], true),
            self::Quoted     => in_array($target, [self::Won, self::Lost, self::Cancelled], true),
            default          => false,
        };
    }
}
