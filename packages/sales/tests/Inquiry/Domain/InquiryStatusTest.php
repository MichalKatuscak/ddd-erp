<?php
declare(strict_types=1);

namespace Sales\Tests\Inquiry\Domain;

use Sales\Inquiry\Domain\InquiryStatus;
use Sales\Inquiry\Domain\InvalidStatusTransitionException;
use PHPUnit\Framework\TestCase;

final class InquiryStatusTest extends TestCase
{
    public function test_linear_next_from_new(): void
    {
        $this->assertSame(InquiryStatus::InProgress, InquiryStatus::New->next());
    }

    public function test_linear_next_from_in_progress(): void
    {
        $this->assertSame(InquiryStatus::Quoted, InquiryStatus::InProgress->next());
    }

    public function test_next_from_quoted_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);
        InquiryStatus::Quoted->next();
    }

    public function test_next_from_terminal_throws(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);
        InquiryStatus::Won->next();
    }

    public function test_can_transition_to_won_from_quoted(): void
    {
        $this->assertTrue(InquiryStatus::Quoted->canTransitionTo(InquiryStatus::Won));
    }

    public function test_cannot_transition_to_won_from_new(): void
    {
        $this->assertFalse(InquiryStatus::New->canTransitionTo(InquiryStatus::Won));
    }

    public function test_can_cancel_from_any_non_terminal(): void
    {
        $this->assertTrue(InquiryStatus::New->canTransitionTo(InquiryStatus::Cancelled));
        $this->assertTrue(InquiryStatus::InProgress->canTransitionTo(InquiryStatus::Cancelled));
        $this->assertTrue(InquiryStatus::Quoted->canTransitionTo(InquiryStatus::Cancelled));
    }
}
