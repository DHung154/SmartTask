<?php

namespace App\Support;

final class TaskProgress
{
    public static function normalize(mixed $value): int
    {
        return max(0, min(100, (int) $value));
    }

    public static function isComplete(int $progress): bool
    {
        return $progress === 100;
    }
}
