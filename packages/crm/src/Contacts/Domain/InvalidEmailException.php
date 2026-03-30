<?php
declare(strict_types=1);

namespace Crm\Contacts\Domain;

final class InvalidEmailException extends \DomainException
{
    public function __construct(string $email)
    {
        parent::__construct("Invalid email address: '$email'");
    }
}
