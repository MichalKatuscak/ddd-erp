<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http\Request;
use Symfony\Component\Validator\Constraints as Assert;
final class UpdateInquiryRequest
{
    public function __construct(
        #[Assert\NotBlank] public readonly string $customer_name = '',
        #[Assert\NotBlank] #[Assert\Email] public readonly string $contact_email = '',
        #[Assert\NotBlank] public readonly string $description = '',
        public readonly ?string $customer_id = null,
        public readonly ?string $requested_deadline = null,
        #[Assert\Type('array')] public readonly array $required_roles = [],
    ) {}
}
