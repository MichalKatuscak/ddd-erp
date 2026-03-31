<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateCustomerRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $first_name = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $last_name = '',
    ) {}
}
