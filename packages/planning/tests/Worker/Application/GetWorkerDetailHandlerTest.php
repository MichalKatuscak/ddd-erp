<?php

declare(strict_types=1);

namespace Planning\Tests\Worker\Application;

use PHPUnit\Framework\TestCase;
use Planning\Worker\Application\GetWorkerDetail\GetWorkerDetailHandler;
use Planning\Worker\Application\GetWorkerDetail\GetWorkerDetailQuery;

final class GetWorkerDetailHandlerTest extends TestCase
{
    public function testHandlerExists(): void
    {
        $this->assertTrue(class_exists(GetWorkerDetailHandler::class));
        $this->assertTrue(class_exists(GetWorkerDetailQuery::class));
    }
}
