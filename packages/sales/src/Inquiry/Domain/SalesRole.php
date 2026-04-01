<?php
declare(strict_types=1);

namespace Sales\Inquiry\Domain;

enum SalesRole: string
{
    case Designer = 'designer';
    case Frontend = 'frontend';
    case Backend  = 'backend';
    case Pm       = 'pm';
    case Qa       = 'qa';
    case Devops   = 'devops';
}
