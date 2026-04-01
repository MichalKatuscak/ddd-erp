<?php
declare(strict_types=1);
namespace Sales\Quote\Domain;
enum QuoteStatus: string
{
    case Draft    = 'draft';
    case Sent     = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
