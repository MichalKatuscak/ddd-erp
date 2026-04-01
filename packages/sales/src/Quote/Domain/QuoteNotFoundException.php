<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
final class QuoteNotFoundException extends \DomainException
{
    public function __construct(string $id) { parent::__construct("Quote '$id' not found"); }
}
