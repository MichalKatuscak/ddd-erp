<?php
declare(strict_types=1);

namespace SharedKernel\Tests\Domain;

use PHPUnit\Framework\TestCase;
use SharedKernel\Domain\AggregateRoot;
use SharedKernel\Domain\DomainEvent;

final class AggregateRootTest extends TestCase
{
    public function test_pulls_recorded_events_and_clears_them(): void
    {
        $aggregate = new class extends AggregateRoot {
            public function doSomething(): void
            {
                $this->recordEvent(new class extends DomainEvent {});
                $this->recordEvent(new class extends DomainEvent {});
            }
        };

        $aggregate->doSomething();

        $events = $aggregate->pullDomainEvents();
        $this->assertCount(2, $events);

        // Po pull jsou události vymazány
        $this->assertCount(0, $aggregate->pullDomainEvents());
    }

    public function test_domain_event_records_occurred_at(): void
    {
        $before = new \DateTimeImmutable();
        $event = new class extends DomainEvent {};
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $event->occurredAt);
        $this->assertLessThanOrEqual($after, $event->occurredAt);
    }
}
