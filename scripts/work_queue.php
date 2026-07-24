<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Queue;
use App\Models\Task;

$mailer = new Mailer();
$taskModel = new Task();
$processed = 0;

while ($job = Queue::reserve('deadline_email')) {
    try {
        $payload = $job['payload'];
        $mailer->send($payload['email'], 'TodoPHP - Canh bao tre deadline', $payload['html']);
        foreach ($payload['task_ids'] as $taskId) {
            $taskModel->markReminderSent((int) $taskId);
        }
        Queue::complete((int) $job['id']);
        Logger::info('queue.deadline_email.completed', ['job_id' => $job['id']]);
        $processed++;
    } catch (Throwable $exception) {
        Queue::fail((int) $job['id'], (int) $job['attempts'] + 1, $exception->getMessage());
        Logger::error('queue.deadline_email.failed', ['job_id' => $job['id'], 'error' => $exception->getMessage()]);
    }
}

echo "Processed {$processed} queued email job(s).\n";
