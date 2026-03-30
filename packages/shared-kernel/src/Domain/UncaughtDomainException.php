<?php
declare(strict_types=1);

namespace SharedKernel\Domain;

/**
 * Marker interface for domain exceptions that should NOT be caught
 * by the DomainExceptionListener and should propagate as 500 errors.
 */
interface UncaughtDomainException
{
}
