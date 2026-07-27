<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

/**
 * Lưới an toàn cho việc lặp lại: lần kế tiếp bình thường được sinh ngay lúc
 * người dùng tick hoàn thành. Command này lo các việc lặp đã quá hạn mà
 * không ai tick, để chuỗi lặp không bị đứt.
 */
class GenerateRecurringTasks extends Command
{
    protected $signature = 'tasks:generate-recurring';

    protected $description = 'Sinh lần kế tiếp cho các công việc lặp lại đã qua hạn';

    public function handle(): int
    {
        $tasks = Task::where('repeat', '!=', 'none')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->get();

        $created = 0;

        foreach ($tasks as $task) {
            $next = $task->spawnNextOccurrence();
            if ($next) {
                $created++;
                $this->line('Đã tạo: ' . $next->title . ' - hạn ' . $next->due_date->toDateString());
            }
        }

        $this->info("Đã sinh {$created} công việc lặp lại.");

        return self::SUCCESS;
    }
}
