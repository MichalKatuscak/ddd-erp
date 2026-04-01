<?php
declare(strict_types=1);
namespace Sales\Quote\Application\ExportQuotePdf;
final readonly class ExportQuotePdfCommand { public function __construct(public string $quoteId) {} }
