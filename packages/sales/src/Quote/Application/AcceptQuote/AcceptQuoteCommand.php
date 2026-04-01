<?php
declare(strict_types=1);
namespace Sales\Quote\Application\AcceptQuote;
final readonly class AcceptQuoteCommand { public function __construct(public string $quoteId) {} }
