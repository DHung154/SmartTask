<?php

namespace App\Middleware;

use App\Core\Eloquent;
use App\Core\JsonResponse;
use App\Entities\UserRecord;

final class ApiTokenMiddleware
{
    public function handle(): void
    {
        Eloquent::boot();
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? $headers['Authorization']
            ?? '';
        if (!preg_match('/^Bearer\\s+(.+)$/i', $header, $matches)) {
            JsonResponse::send(['message' => 'Missing bearer token.'], 401);
        }
        $user = UserRecord::query()->where('api_token', hash('sha256', trim($matches[1])))->first();
        if ($user === null) {
            JsonResponse::send(['message' => 'Invalid bearer token.'], 401);
        }
        $_SERVER['APP_API_USER_ID'] = (string) $user->id;
        $_SERVER['APP_API_ROLE'] = (string) $user->role;
    }
}
