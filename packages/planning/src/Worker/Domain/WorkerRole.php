<?php
declare(strict_types=1);

namespace Planning\Worker\Domain;

enum WorkerRole: string
{
    case Designer = 'designer';
    case Frontend = 'frontend';
    case Backend  = 'backend';
    case Pm       = 'pm';
    case Qa       = 'qa';
    case Devops   = 'devops';
}
