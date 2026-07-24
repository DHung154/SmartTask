<?php

namespace App\Middleware;

use App\Core\JsonResponse;

final class ApiAdminMiddleware
{
    public function handle(): void
    {
        if (($_SERVER['APP_API_ROLE'] ?? '') !== 'admin') {
            JsonResponse::send(['message' => 'Admin role required.'], 403);
        }
    }
}
