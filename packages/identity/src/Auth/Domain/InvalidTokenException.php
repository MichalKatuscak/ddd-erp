<?php
// packages/identity/src/Auth/Domain/InvalidTokenException.php
declare(strict_types=1);

namespace Identity\Auth\Domain;

final class InvalidTokenException extends \DomainException
{
    public function __construct(string $message = 'Invalid or expired token')
    {
        parent::__construct($message);
    }
}
