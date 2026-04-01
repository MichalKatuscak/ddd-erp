<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Http\Request;
use Symfony\Component\Validator\Constraints as Assert;
final class UpdateQuotePhaseRequest
{
    public function __construct(
        #[Assert\NotBlank] public readonly string $name = '',
        #[Assert\NotBlank] public readonly string $required_role = '',
        #[Assert\Positive] public readonly int    $duration_days = 1,
        #[Assert\Positive] public readonly int    $daily_rate_amount = 0,
        public readonly string $daily_rate_currency = 'CZK',
    ) {}
}
