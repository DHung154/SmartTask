<?php
require_once __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
$db = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$dir = __DIR__ . '/../storage/backups';
if (!is_dir($dir) && !mkdir($dir, 0775, true)) throw new RuntimeException('Khong tao duoc thu muc backup.');
$file = $dir . '/todo-' . date('Ymd-His') . '.sql';
$handle = fopen($file, 'wb');
$quoteName = fn($name) => chr(96) . str_replace(chr(96), chr(96) . chr(96), $name) . chr(96);
fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
foreach ($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
    $tableName = $quoteName($table);
    $create = $db->query("SHOW CREATE TABLE {$tableName}")->fetch();
    fwrite($handle, "DROP TABLE IF EXISTS {$tableName};\n" . array_values($create)[1] . ";\n\n");
    foreach ($db->query("SELECT * FROM {$tableName}") as $row) {
        $columns = array_map($quoteName, array_keys($row));
        $values = array_map(fn($value) => $value === null ? 'NULL' : $db->quote((string)$value), array_values($row));
        fwrite($handle, "INSERT INTO {$tableName} (" . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ");\n");
    }
    fwrite($handle, "\n");
}
fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
fclose($handle);
echo $file . PHP_EOL;
