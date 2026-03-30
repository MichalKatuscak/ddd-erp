<?php
// packages/identity/src/Auth/Domain/InvalidTokenException.php
declare(strict_types=1);

namespace Identity\Auth\Domain;

use SharedKernel\Domain\UncaughtDomainException;

final class InvalidTokenException extends \DomainException implements UncaughtDomainException
{
    public function __construct(string $message = 'Invalid or expired token')
    {
        parent::__construct($message);
    }
}
