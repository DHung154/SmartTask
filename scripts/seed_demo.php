<?php
require_once __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
$db = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$email = $argv[1] ?? 'test@example.com';
$find = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$find->execute([':email' => $email]);
$userId = $find->fetchColumn();
if (!$userId) {
    $createUser = $db->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
    $createUser->execute([':name' => 'Demo User', ':email' => $email, ':password' => password_hash('123456', PASSWORD_DEFAULT)]);
    $userId = $db->lastInsertId();
}
$listName = "\u{0110}\u{1ED3} \u{00E1}n cu\u{1ED1}i m\u{00F4}n";
$listStmt = $db->prepare('SELECT id FROM lists WHERE user_id = :user_id AND name = :name LIMIT 1');
$listStmt->execute([':user_id' => $userId, ':name' => $listName]);
$listId = $listStmt->fetchColumn();
if (!$listId) {
    $createList = $db->prepare('INSERT INTO lists (user_id, name) VALUES (:user_id, :name)');
    $createList->execute([':user_id' => $userId, ':name' => $listName]);
    $listId = $db->lastInsertId();
}
$samples = [
    ["Ho\u{00E0}n thi\u{1EC7}n giao di\u{1EC7}n Kanban", 25, 'high', 1, '+1 day'],
    ["Ki\u{1EC3}m th\u{1EED} ch\u{1EE9}c n\u{0103}ng Gmail", 50, 'high', 1, '+2 days'],
    ["Vi\u{1EBF}t b\u{00E1}o c\u{00E1}o \u{0111}\u{1ED3} \u{00E1}n", 75, 'normal', 1, '+4 days'],
    ["Chu\u{1EA9}n b\u{1ECB} slide thuy\u{1EBF}t tr\u{00EC}nh", 0, 'normal', 1, '+6 days'],
    ["Sao l\u{01B0}u d\u{1EEF} li\u{1EC7}u tr\u{01B0}\u{1EDB}c khi demo", 100, 'low', 0, '-1 day'],
    ["Mua t\u{00E0}i li\u{1EC7}u h\u{1ECD}c t\u{1EAD}p", 0, 'low', 0, '+3 days'],
];
$exists = $db->prepare('SELECT id FROM tasks WHERE user_id = :user_id AND title = :title LIMIT 1');
$insert = $db->prepare('INSERT INTO tasks (user_id, list_id, title, description, is_important, priority, progress, due_date, completed, created_at) VALUES (:user_id, :list_id, :title, :description, :important, :priority, :progress, :due_date, :completed, NOW())');
$added = 0;
foreach ($samples as [$title, $progress, $priority, $important, $date]) {
    $exists->execute([':user_id' => $userId, ':title' => $title]);
    if ($exists->fetchColumn()) continue;
    $insert->execute([':user_id' => $userId, ':list_id' => $listId, ':title' => $title, ':description' => "D\u{1EEF} li\u{1EC7}u m\u{1EAB}u ph\u{1EE5}c v\u{1EE5} demo \u{0111}\u{1ED3} \u{00E1}n.", ':important' => $important, ':priority' => $priority, ':progress' => $progress, ':due_date' => date('Y-m-d', strtotime($date)), ':completed' => (int)($progress === 100)]);
    $added++;
}
echo "Da them {$added} cong viec demo cho {$email}.\n";
