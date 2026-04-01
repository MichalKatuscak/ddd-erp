<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
use Sales\Inquiry\Domain\InquiryId;
use SharedKernel\Domain\AggregateRoot;
final class Quote extends AggregateRoot
{
    /** @var QuotePhase[] */
    private array $phases;
    private Money $totalPrice;
    private function __construct(
        private readonly QuoteId    $id,
        private readonly InquiryId  $inquiryId,
        private \DateTimeImmutable  $validUntil,
        private QuoteStatus         $status,
        private ?string             $pdfPath,
        private string              $notes,
        array                       $phases,
    ) {
        $this->phases     = $phases;
        $this->totalPrice = $this->computeTotal();
    }
    public static function create(QuoteId $id, InquiryId $inquiryId, \DateTimeImmutable $validUntil, string $notes): self
    {
        return new self($id, $inquiryId, $validUntil, QuoteStatus::Draft, null, $notes, []);
    }
    /** @param QuotePhase[] $phases */
    public static function reconstruct(QuoteId $id, InquiryId $inquiryId, \DateTimeImmutable $validUntil, QuoteStatus $status, ?string $pdfPath, string $notes, array $phases): self
    {
        return new self($id, $inquiryId, $validUntil, $status, $pdfPath, $notes, $phases);
    }
    public function addPhase(QuotePhase $phase): void
    {
        $this->phases[]   = $phase;
        $this->totalPrice = $this->computeTotal();
    }
    public function updatePhase(QuotePhaseId $phaseId, string $name, \Sales\Inquiry\Domain\SalesRole $role, int $durationDays, Money $dailyRate): void
    {
        foreach ($this->phases as $i => $phase) {
            if ($phase->id()->equals($phaseId)) {
                $this->phases[$i] = $phase->update($name, $role, $durationDays, $dailyRate);
                $this->totalPrice = $this->computeTotal();
                return;
            }
        }
        throw new \DomainException("Phase '{$phaseId->value()}' not found in quote");
    }
    public function send(): void
    {
        if ($this->status !== QuoteStatus::Draft) {
            throw new \DomainException("Only draft quotes can be sent");
        }
        $this->status = QuoteStatus::Sent;
    }
    public function accept(): void
    {
        if ($this->status !== QuoteStatus::Sent) {
            throw new \DomainException("Only sent quotes can be accepted");
        }
        $this->status = QuoteStatus::Accepted;
        $this->recordEvent(new QuoteAccepted($this->id, $this->inquiryId));
    }
    public function reject(): void
    {
        if ($this->status !== QuoteStatus::Sent) {
            throw new \DomainException("Only sent quotes can be rejected");
        }
        $this->status = QuoteStatus::Rejected;
    }
    public function markPdfGenerated(string $pdfPath): void
    {
        $this->pdfPath = $pdfPath;
    }
    private function computeTotal(): Money
    {
        $total = new Money(0, 'CZK');
        foreach ($this->phases as $phase) {
            $total = $total->add($phase->subtotal);
        }
        return $total;
    }
    public function id(): QuoteId { return $this->id; }
    public function inquiryId(): InquiryId { return $this->inquiryId; }
    public function validUntil(): \DateTimeImmutable { return $this->validUntil; }
    public function status(): QuoteStatus { return $this->status; }
    public function pdfPath(): ?string { return $this->pdfPath; }
    public function notes(): string { return $this->notes; }
    /** @return QuotePhase[] */
    public function phases(): array { return $this->phases; }
    public function totalPrice(): Money { return $this->totalPrice; }
}
