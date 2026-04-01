<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
final class Money
{
    public function __construct(
        public readonly int    $amount,
        public readonly string $currency,
    ) {}
    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->currency}");
        }
        return new self($this->amount + $other->amount, $this->currency);
    }
    public function multiply(int $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }
    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
}
