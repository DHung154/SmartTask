<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log($userId, $action, $entityType, $entityId, $message): void
    {
        ActivityLog::log($userId, $action, $entityType, $entityId, $message);
    }
}
