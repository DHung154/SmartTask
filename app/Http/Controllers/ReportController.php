<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use App\Models\Task;
use App\Models\TodoList;
use App\Models\ActivityLog;

class ReportController extends Controller
{
    public function calendar(Request $request)
    {
        $userId = auth()->id();
        $monthInput = $request->query('month', '');
        $month = preg_match('/^\d{4}-\d{2}$/', $monthInput) ? $monthInput : Carbon::now()->format('Y-m');

        $start = $month . '-01';
        $end = Carbon::parse($start)->endOfMonth()->toDateString();

        $selectedDate = $request->query('day', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) || $selectedDate < $start || $selectedDate > $end) {
            $selectedDate = '';
        }

        $tasks = Task::where('user_id', $userId)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $start)
            ->whereDate('due_date', '<=', $end)
            ->orderBy('due_date')
            ->get();

        $selectedTasks = $selectedDate !== ''
            ? $tasks->filter(fn($task) => $task->due_date->toDateString() === $selectedDate)->values()
            : collect([]);

        return view('reports.calendar', $this->baseData() + [
            'title'         => 'Lịch deadline',
            'active_page'   => 'calendar',
            'month'         => $month,
            'tasks'         => $tasks,
            'selectedDate'  => $selectedDate,
            'selectedTasks' => $selectedTasks,
        ]);
    }

    public function report()
    {
        $userId = auth()->id();

        $priorityCounts = Task::where('user_id', $userId)
            ->selectRaw("priority, COUNT(*) as count")
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $monthlySummary = Task::where('user_id', $userId)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total, SUM(completed) as completed")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderByDesc('month')
            ->limit(12)
            ->get()
            ->toArray();

        return view('reports.report', $this->baseData() + [
            'title'          => 'Báo cáo',
            'active_page'    => 'report',
            'priorityCounts' => $priorityCounts,
            'monthlySummary' => $monthlySummary,
        ]);
    }

    public function activity()
    {
        $userId = auth()->id();

        $logs = ActivityLog::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('reports.activity', $this->baseData() + [
            'title'       => 'Nhật ký',
            'active_page' => 'activity',
            'logs'        => $logs,
        ]);
    }

    public function exportCsv()
    {
        $tasks = Task::where('user_id', auth()->id())->get();

        $filename = 'todo-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($tasks) {
            $out = fopen('php://output', 'w');
            // Write BOM for UTF-8
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tên công việc', 'Mô tả', 'Ưu tiên', 'Tiến độ', 'Hạn chót', 'Trạng thái']);

            foreach ($tasks as $task) {
                fputcsv($out, [
                    $task->title,
                    $task->description ?? '',
                    $task->priority ?? 'normal',
                    (int) ($task->progress ?? 0) . '%',
                    $task->due_date ? $task->due_date->toDateString() : '',
                    $task->completed ? 'Hoàn thành' : 'Chưa hoàn thành',
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function baseData(): array
    {
        $userId = auth()->id();
        return Cache::remember('dashboard-user-' . $userId, 90, function () use ($userId) {
            $userLists = TodoList::where('user_id', $userId)->orderBy('created_at')->get();
            return [
                'userLists'  => $userLists,
                'taskCounts' => Task::getTaskCounts($userId, $userLists),
                'stats'      => Task::getStatistics($userId),
            ];
        });
    }
}
