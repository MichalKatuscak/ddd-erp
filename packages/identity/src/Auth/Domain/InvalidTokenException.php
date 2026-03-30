<?php
// packages/identity/src/Auth/Domain/InvalidTokenException.php
declare(strict_types=1);

namespace Identity\Auth\Domain;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class InvalidTokenException extends \DomainException implements HttpExceptionInterface
{
    public function __construct(string $message = 'Invalid or expired token')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return 401;
    }

    public function getHeaders(): array
    {
        return [];
    }
}
