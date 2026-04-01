<?php
declare(strict_types=1);
namespace Sales\Quote\Application\RejectQuote;
final readonly class RejectQuoteCommand { public function __construct(public string $quoteId) {} }
