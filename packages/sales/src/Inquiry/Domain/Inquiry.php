<?php
declare(strict_types=1);

namespace Sales\Inquiry\Domain;

use SharedKernel\Domain\AggregateRoot;

final class Inquiry extends AggregateRoot
{
    /** @var RequiredRole[] */
    private array $requiredRoles;
    /** @var Attachment[] */
    private array $attachments;

    private function __construct(
        private readonly InquiryId          $id,
        private ?string                      $customerId,
        private string                       $customerName,
        private string                       $contactEmail,
        private string                       $description,
        private ?\DateTimeImmutable          $requestedDeadline,
        array                                $requiredRoles,
        array                                $attachments,
        private InquiryStatus               $status,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->requiredRoles = $requiredRoles;
        $this->attachments   = $attachments;
    }

    /** @param RequiredRole[] $requiredRoles */
    public static function create(
        InquiryId          $id,
        ?string            $customerId,
        string             $customerName,
        string             $contactEmail,
        string             $description,
        ?\DateTimeImmutable $requestedDeadline,
        array              $requiredRoles,
    ): self {
        $inquiry = new self(
            $id, $customerId, $customerName, $contactEmail,
            $description, $requestedDeadline, $requiredRoles, [],
            InquiryStatus::New, new \DateTimeImmutable(),
        );
        $inquiry->recordEvent(new InquiryCreated($id));
        return $inquiry;
    }

    /** @param RequiredRole[] $requiredRoles @param Attachment[] $attachments */
    public static function reconstruct(
        InquiryId          $id,
        ?string            $customerId,
        string             $customerName,
        string             $contactEmail,
        string             $description,
        ?\DateTimeImmutable $requestedDeadline,
        array              $requiredRoles,
        array              $attachments,
        InquiryStatus      $status,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $customerId, $customerName, $contactEmail,
            $description, $requestedDeadline, $requiredRoles, $attachments,
            $status, $createdAt);
    }

    public function update(
        ?string $customerId,
        string  $customerName,
        string  $contactEmail,
        string  $description,
        ?\DateTimeImmutable $requestedDeadline,
        array   $requiredRoles,
    ): void {
        $this->customerId        = $customerId;
        $this->customerName      = $customerName;
        $this->contactEmail      = $contactEmail;
        $this->description       = $description;
        $this->requestedDeadline = $requestedDeadline;
        $this->requiredRoles     = $requiredRoles;
    }

    public function advanceStatus(?string $targetStatus): void
    {
        if ($targetStatus === null) {
            $this->status = $this->status->next();
            return;
        }
        $target = InquiryStatus::from($targetStatus);
        if (!$this->status->canTransitionTo($target)) {
            throw new InvalidStatusTransitionException(
                "Cannot transition from '{$this->status->value}' to '{$target->value}'"
            );
        }
        $this->status = $target;
    }

    public function addAttachment(Attachment $attachment): void
    {
        $this->attachments[] = $attachment;
    }

    public function id(): InquiryId { return $this->id; }
    public function customerId(): ?string { return $this->customerId; }
    public function customerName(): string { return $this->customerName; }
    public function contactEmail(): string { return $this->contactEmail; }
    public function description(): string { return $this->description; }
    public function requestedDeadline(): ?\DateTimeImmutable { return $this->requestedDeadline; }
    /** @return RequiredRole[] */
    public function requiredRoles(): array { return $this->requiredRoles; }
    /** @return Attachment[] */
    public function attachments(): array { return $this->attachments; }
    public function status(): InquiryStatus { return $this->status; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
}
