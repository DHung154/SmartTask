<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use App\Core\Logger;
use App\Core\Queue;
use App\Models\Task;

$taskModel = new Task();
$tasks = $taskModel->getOverdueTasksForReminders();

if (empty($tasks)) {
    echo "Khong co cong viec qua han can nhac.\n";
    exit(0);
}

$byUser = [];
foreach ($tasks as $task) {
    $byUser[$task['user_id']]['email'] = $task['email'];
    $byUser[$task['user_id']]['name'] = $task['name'];
    $byUser[$task['user_id']]['tasks'][] = $task;
}

$appUrl = rtrim(Env::get('APP_URL', 'http://todophp.test'), '/');
$queued = 0;

foreach ($byUser as $user) {
    $items = '';
    foreach ($user['tasks'] as $task) {
        $url = $appUrl . '/tasks/edit?id=' . (int)$task['id'];
        $items .= '<li><strong>' . htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') . '</strong>'
            . ' - han: ' . htmlspecialchars(date('d/m/Y', strtotime($task['due_date'])), ENT_QUOTES, 'UTF-8')
            . ' - <a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">mo cong viec</a></li>';
    }

    $html = '<p>Chao ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>Ban co cong viec da tre deadline:</p><ul>' . $items . '</ul>'
        . '<p>Mo TodoPHP de cap nhat tien do nhe.</p>';

    try {
        Queue::push('deadline_email', [
            'email' => $user['email'],
            'html' => $html,
            'task_ids' => array_column($user['tasks'], 'id'),
        ]);
        foreach ($user['tasks'] as $task) {
            $taskModel->markReminderQueued($task['id']);
            $queued++;
        }
    } catch (Throwable $exception) {
        Logger::error('queue.deadline_email.enqueue_failed', ['error' => $exception->getMessage()]);
    }
}

echo "Da dua {$queued} cong viec qua han vao email queue.\n";
