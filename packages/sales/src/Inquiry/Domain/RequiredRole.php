<?php
declare(strict_types=1);

namespace Sales\Inquiry\Domain;

final class RequiredRole
{
    /** @param string[] $skills */
    public function __construct(
        public readonly SalesRole $role,
        public readonly array     $skills,
    ) {}
}
