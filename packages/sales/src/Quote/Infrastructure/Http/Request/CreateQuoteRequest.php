<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http\Request;
use Symfony\Component\Validator\Constraints as Assert;
final class CreateQuoteRequest
{
    public function __construct(
        #[Assert\NotBlank] public readonly string $valid_until = '',
        public readonly string $notes = '',
    ) {}
}
