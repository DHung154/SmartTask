<?php

namespace App\Middleware;

use App\Core\Controller;
use App\Core\Session;

final class AdminMiddleware
{
    public function handle(): void
    {
        if (Session::get('user_role') !== 'admin') {
            Controller::abort('Khong duoc phep truy cap', '', 403);
        }
    }
}
