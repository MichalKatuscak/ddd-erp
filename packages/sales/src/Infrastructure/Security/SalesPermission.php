<?php
declare(strict_types=1);
namespace Sales\Infrastructure\Security;
enum SalesPermission: string
{
    case ManageInquiries = 'sales.inquiries.manage';
    case ManageQuotes    = 'sales.quotes.manage';
}
