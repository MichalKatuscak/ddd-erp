<?php
declare(strict_types=1);

namespace Planning\Order\Application\GetPhaseSuggestions;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetPhaseSuggestionsHandler
{
    public function __construct(private readonly Connection $connection) {}

    /** @return CandidateDTO[] */
    public function __invoke(GetPhaseSuggestionsQuery $query): array
    {
        $phase = $this->connection->executeQuery(
            'SELECT required_role, required_skills, start_date, end_date
             FROM planning_phases
             WHERE id = :phaseId AND order_id = :orderId',
            ['phaseId' => $query->phaseId, 'orderId' => $query->orderId],
        )->fetchAssociative();

        if (!$phase) {
            throw new \DomainException("Phase not found: '{$query->phaseId}'");
        }
        if (!$phase['start_date'] || !$phase['end_date']) {
            throw new \DomainException('Phase has not been scheduled yet. Run schedule first.');
        }

        $requiredRole   = $phase['required_role'];
        $requiredSkills = json_decode($phase['required_skills'], true) ?? [];
        $startDate      = $phase['start_date'];
        $endDate        = $phase['end_date'];

        // Load all workers with their current allocation in the phase window
        $rows = $this->connection->executeQuery(
            "SELECT w.id, w.primary_role, w.skills,
                    u.first_name || ' ' || u.last_name AS name,
                    COALESCE(SUM(wa.allocation_percent), 0) AS allocated_percent
             FROM planning_workers w
             JOIN identity_users u ON u.id = w.id
             LEFT JOIN planning_worker_allocations wa ON wa.worker_id = w.id
                 AND wa.start_date < :end_date AND wa.end_date > :start_date
             GROUP BY w.id, w.primary_role, w.skills, u.first_name, u.last_name
             ORDER BY allocated_percent ASC",
            ['start_date' => $startDate, 'end_date' => $endDate],
        )->fetchAllAssociative();

        $candidates = [];
        foreach ($rows as $row) {
            $allocated     = (int) $row['allocated_percent'];
            $available     = 100 - $allocated;
            $workerSkills  = json_decode($row['skills'], true) ?? [];
            $roleMatch     = $row['primary_role'] === $requiredRole;
            $skillMatch    = !empty(array_intersect($requiredSkills, $workerSkills));

            if (($roleMatch || $skillMatch) && $available > 0) {
                $candidates[] = new CandidateDTO(
                    id: $row['id'],
                    name: $row['name'],
                    primaryRole: $row['primary_role'],
                    skills: $workerSkills,
                    availablePercent: $available,
                );
            }
        }

        return $candidates;
    }
}
