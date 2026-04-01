<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
use Sales\Inquiry\Domain\SalesRole;
final class QuotePhase
{
    public readonly Money $subtotal;
    public function __construct(
        private readonly QuotePhaseId $id,
        private string                $name,
        private SalesRole             $requiredRole,
        private int                   $durationDays,
        private Money                 $dailyRate,
    ) {
        $this->subtotal = $dailyRate->multiply($durationDays);
    }
    public static function reconstruct(QuotePhaseId $id, string $name, SalesRole $role, int $durationDays, Money $dailyRate): self
    {
        return new self($id, $name, $role, $durationDays, $dailyRate);
    }
    public function update(string $name, SalesRole $role, int $durationDays, Money $dailyRate): self
    {
        return new self($this->id, $name, $role, $durationDays, $dailyRate);
    }
    public function id(): QuotePhaseId { return $this->id; }
    public function name(): string { return $this->name; }
    public function requiredRole(): SalesRole { return $this->requiredRole; }
    public function durationDays(): int { return $this->durationDays; }
    public function dailyRate(): Money { return $this->dailyRate; }
}
