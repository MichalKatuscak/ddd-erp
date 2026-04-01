<?php
declare(strict_types=1);
namespace Sales\Quote\Application\SendQuote;
final readonly class SendQuoteCommand { public function __construct(public string $quoteId) {} }
