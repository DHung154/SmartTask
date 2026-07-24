<?php
namespace App\Core;

use PDO;
use PDOException;

class Model
{
    protected static $db = null;

    public function __construct()
    {
        if (self::$db === null) self::$db = $this->connect();
    }

    private function connect()
    {
        $path = __DIR__ . '/../../config/database.php';
        if (!is_file($path)) Controller::abort("Thi\u{1EBF}u file c\u{1EA5}u h\u{00EC}nh", "H\u{00E3}y t\u{1EA1}o file .env.");
        $config = require $path;
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
            return new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            Controller::abort("Kh\u{00F4}ng k\u{1EBF}t n\u{1ED1}i \u{0111}\u{01B0}\u{1EE3}c database", $e->getMessage());
        }
    }

    protected function getDb() { return self::$db; }
}
