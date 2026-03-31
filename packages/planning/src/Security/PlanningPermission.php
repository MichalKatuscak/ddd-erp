<?php
declare(strict_types=1);

namespace Planning\Security;

enum PlanningPermission: string
{
    case ManageOrders  = 'planning.orders.manage';
    case ManageWorkers = 'planning.workers.manage';
}
