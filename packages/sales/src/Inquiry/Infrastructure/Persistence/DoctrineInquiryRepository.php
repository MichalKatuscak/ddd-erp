<?php
declare(strict_types=1);

namespace Sales\Inquiry\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Sales\Inquiry\Application\GetInquiryList\InquiryListItemDTO;
use Sales\Inquiry\Domain\{Attachment, Inquiry, InquiryId, InquiryNotFoundException,
    InquiryRepository, InquiryStatus, RequiredRole, SalesRole};

final class DoctrineInquiryRepository implements InquiryRepository
{
    public function __construct(private readonly Connection $connection) {}

    public function get(InquiryId $id): Inquiry
    {
        $row = $this->connection->executeQuery(
            'SELECT id, customer_id, customer_name, contact_email, description,
                    requested_deadline, required_roles, status, created_at
             FROM sales_inquiries WHERE id = :id',
            ['id' => $id->value()],
        )->fetchAssociative();

        if (!$row) {
            throw new InquiryNotFoundException($id->value());
        }

        $attachmentRows = $this->connection->executeQuery(
            'SELECT id, path, mime_type, original_name FROM sales_inquiry_attachments WHERE inquiry_id = :id ORDER BY created_at',
            ['id' => $id->value()],
        )->fetchAllAssociative();

        return $this->hydrate($row, $attachmentRows);
    }

    /** @return InquiryListItemDTO[] */
    public function findAll(?string $status): array
    {
        $sql = 'SELECT id, customer_name, description, status, requested_deadline, created_at FROM sales_inquiries';
        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY created_at DESC';

        return array_map(
            fn(array $row) => new InquiryListItemDTO(
                $row['id'], $row['customer_name'], $row['description'],
                $row['status'],
                $row['requested_deadline'],
                $row['created_at'],
            ),
            $this->connection->executeQuery($sql, $params)->fetchAllAssociative(),
        );
    }

    public function save(Inquiry $inquiry): void
    {
        $this->connection->executeStatement(
            'INSERT INTO sales_inquiries
                (id, customer_id, customer_name, contact_email, description, requested_deadline, required_roles, status, created_at)
             VALUES (:id, :customer_id, :customer_name, :contact_email, :description, :requested_deadline, :required_roles, :status, :created_at)
             ON CONFLICT (id) DO UPDATE SET
                customer_id = EXCLUDED.customer_id,
                customer_name = EXCLUDED.customer_name,
                contact_email = EXCLUDED.contact_email,
                description = EXCLUDED.description,
                requested_deadline = EXCLUDED.requested_deadline,
                required_roles = EXCLUDED.required_roles,
                status = EXCLUDED.status',
            [
                'id'                => $inquiry->id()->value(),
                'customer_id'       => $inquiry->customerId(),
                'customer_name'     => $inquiry->customerName(),
                'contact_email'     => $inquiry->contactEmail(),
                'description'       => $inquiry->description(),
                'requested_deadline'=> $inquiry->requestedDeadline()?->format('Y-m-d'),
                'required_roles'    => json_encode(array_map(
                    fn(RequiredRole $r) => ['role' => $r->role->value, 'skills' => $r->skills],
                    $inquiry->requiredRoles(),
                )),
                'status'            => $inquiry->status()->value,
                'created_at'        => $inquiry->createdAt()->format('Y-m-d H:i:s'),
            ],
        );

        // sync attachments: insert new ones (upsert by id)
        foreach ($inquiry->attachments() as $attachment) {
            $this->connection->executeStatement(
                'INSERT INTO sales_inquiry_attachments (id, inquiry_id, path, mime_type, original_name, created_at)
                 VALUES (:id, :inquiry_id, :path, :mime_type, :original_name, NOW())
                 ON CONFLICT (id) DO NOTHING',
                [
                    'id'           => $attachment->id,
                    'inquiry_id'   => $inquiry->id()->value(),
                    'path'         => $attachment->path,
                    'mime_type'    => $attachment->mimeType,
                    'original_name'=> $attachment->originalName,
                ],
            );
        }
    }

    private function hydrate(array $row, array $attachmentRows): Inquiry
    {
        $roles = array_map(
            fn(array $r) => new RequiredRole(SalesRole::from($r['role']), $r['skills'] ?? []),
            json_decode($row['required_roles'], true) ?? [],
        );
        $attachments = array_map(
            fn(array $a) => new Attachment($a['path'], $a['mime_type'], $a['original_name'], $a['id']),
            $attachmentRows,
        );
        return Inquiry::reconstruct(
            InquiryId::fromString($row['id']),
            $row['customer_id'],
            $row['customer_name'],
            $row['contact_email'],
            $row['description'],
            $row['requested_deadline'] ? new \DateTimeImmutable($row['requested_deadline']) : null,
            $roles,
            $attachments,
            InquiryStatus::from($row['status']),
            new \DateTimeImmutable($row['created_at']),
        );
    }
}
