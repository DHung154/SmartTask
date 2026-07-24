<?php

namespace App\Controllers;

use App\Core\Eloquent;
use App\Core\JsonResponse;
use App\Core\Logger;

final class HealthController
{
    public function index(): never
    {
        try {
            Eloquent::boot()->getConnection()->getPdo();
            JsonResponse::send(['status' => 'ok', 'database' => 'connected']);
        } catch (\Throwable $exception) {
            Logger::error('health.failed', ['error' => $exception->getMessage()]);
            JsonResponse::send(['status' => 'error', 'database' => 'unavailable'], 503);
        }
    }
}
