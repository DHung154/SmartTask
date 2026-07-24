<?php

namespace App\Middleware;

use App\Core\Session;

final class AuthMiddleware
{
    public function handle(): void
    {
        if (!Session::has('user_id')) {
            header('Location: /login');
            exit;
        }
    }
}
