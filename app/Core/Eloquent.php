<?php

namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;

final class Eloquent
{
    private static ?Capsule $capsule = null;

    public static function boot(): Capsule
    {
        if (self::$capsule !== null) {
            return self::$capsule;
        }

        $config = require __DIR__ . '/../../config/database.php';
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'mysql', 'host' => $config['host'], 'port' => $config['port'],
            'database' => $config['dbname'], 'username' => $config['username'],
            'password' => $config['password'], 'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci', 'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return self::$capsule = $capsule;
    }
}
