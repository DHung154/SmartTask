<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDueReminders extends Command
{
    protected $signature = 'tasks:send-reminders {--days=1 : Include tasks due within this many days}';

    protected $description = 'Send Gmail SMTP reminders for upcoming task deadlines';

    public function handle(): int
    {
        $days = max(0, min(30, (int) $this->option('days')));
        $from = today();
        $until = today()->addDays($days);
        $sent = 0;

        $tasks = Task::with('user')
            ->where('completed', false)
            ->whereNotNull('due_date')
            ->whereNull('reminder_sent_at')
            ->whereBetween('due_date', [$from->toDateString(), $until->toDateString()])
            ->get();

        foreach ($tasks as $task) {
            if (!$task->user?->email) continue;

            try {
                Mail::send('emails.deadline-reminder', ['task' => $task], function ($message) use ($task) {
                    $message->to($task->user->email)
                        ->subject('Sắp đến hạn: ' . $task->title);
                });

                $task->forceFill(['reminder_sent_at' => now()])->saveQuietly();
                $sent++;
            } catch (\Throwable $e) {
                $this->error('Không gửi được cho ' . $task->user->email . ': ' . $e->getMessage());
            }
        }

        $this->info("Đã gửi {$sent} email nhắc deadline.");
        return self::SUCCESS;
    }
}
