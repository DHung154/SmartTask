<?php

namespace Tests\Unit;

use App\Support\TaskProgress;
use PHPUnit\Framework\TestCase;

final class TaskProgressTest extends TestCase
{
    public function testProgressIsKeptInsideTheSupportedRange(): void
    {
        self::assertSame(0, TaskProgress::normalize(-8));
        self::assertSame(45, TaskProgress::normalize('45'));
        self::assertSame(100, TaskProgress::normalize(120));
    }

    public function testOnlyOneHundredPercentCompletesATask(): void
    {
        self::assertFalse(TaskProgress::isComplete(99));
        self::assertTrue(TaskProgress::isComplete(100));
    }
}
