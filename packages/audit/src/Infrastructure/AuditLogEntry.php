<?php
declare(strict_types=1);

namespace Audit\Infrastructure;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_log')]
final class AuditLogEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $eventType;

    #[ORM\Column(type: 'string', length: 36)]
    private string $aggregateId;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $performedBy;

    #[ORM\Column(type: 'datetimetz_immutable')]
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        string $id,
        string $eventType,
        string $aggregateId,
        array $payload,
        ?string $performedBy,
        \DateTimeImmutable $occurredAt,
    ) {
        $this->id          = $id;
        $this->eventType   = $eventType;
        $this->aggregateId = $aggregateId;
        $this->payload     = $payload;
        $this->performedBy = $performedBy;
        $this->occurredAt  = $occurredAt;
    }

    public function eventType(): string             { return $this->eventType; }
    public function aggregateId(): string           { return $this->aggregateId; }
    public function payload(): array                { return $this->payload; }
    public function performedBy(): ?string          { return $this->performedBy; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
