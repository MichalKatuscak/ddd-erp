<?php
declare(strict_types=1);

namespace Identity\Auth\Domain;

use SharedKernel\Domain\UncaughtDomainException;

final class InvalidCredentialsException extends \DomainException implements UncaughtDomainException
{
    public function __construct(string $message = 'Invalid credentials')
    {
        parent::__construct($message);
    }
}
