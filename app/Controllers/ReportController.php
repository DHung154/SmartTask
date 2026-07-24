<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Cache;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TodoList;

class ReportController extends Controller
{
    private $taskModel;
    private $listModel;
    private $activityLog;

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->listModel = new TodoList();
        $this->activityLog = new ActivityLog();
    }

    public function calendar()
    {
        $this->requireAuth();
        $userId = Session::get('user_id');
        $month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        $selectedDate = $_GET['day'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) || $selectedDate < $start || $selectedDate > $end) {
            $selectedDate = '';
        }
        $tasks = $this->taskModel->getTasksBetweenDates($userId, $start, $end);
        $selectedTasks = array_values(array_filter($tasks, fn($task) => $selectedDate !== '' && $task['due_date'] === $selectedDate));

        $this->view('reports/calendar', $this->baseData($userId) + [
            'title' => "L\u{1ECB}ch deadline",
            'active_page' => 'calendar',
            'month' => $month,
            'tasks' => $tasks,
            'selectedDate' => $selectedDate,
            'selectedTasks' => $selectedTasks,
        ]);
    }

    public function report()
    {
        $this->requireAuth();
        $userId = Session::get('user_id');

        $this->view('reports/report', $this->baseData($userId) + [
            'title' => "B\u{00E1}o c\u{00E1}o",
            'active_page' => 'report',
            'priorityCounts' => $this->taskModel->getPriorityCounts($userId),
            'monthlySummary' => $this->taskModel->getMonthlySummary($userId),
        ]);
    }

    public function activity()
    {
        $this->requireAuth();
        $userId = Session::get('user_id');

        $this->view('reports/activity', $this->baseData($userId) + [
            'title' => "Nh\u{1EAD}t k\u{00FD}",
            'active_page' => 'activity',
            'logs' => $this->activityLog->latest($userId),
        ]);
    }

    public function exportCsv()
    {
        $this->requireAuth();
        $tasks = $this->taskModel->getAllByUser(Session::get('user_id'));
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="todo-report-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ["T\u{00EA}n c\u{00F4}ng vi\u{1EC7}c", "M\u{00F4} t\u{1EA3}", "\u{01AF}u ti\u{00EA}n", "Ti\u{1EBF}n \u{0111}\u{1ED9}", "H\u{1EA1}n ch\u{00F3}t", "Tr\u{1EA1}ng th\u{00E1}i"]);
        foreach ($tasks as $task) {
            fputcsv($out, [
                $task['title'],
                $task['description'] ?? '',
                $task['priority'] ?? 'normal',
                (int)($task['progress'] ?? 0) . '%',
                $task['due_date'] ?? '',
                !empty($task['completed']) ? "Ho\u{00E0}n th\u{00E0}nh" : "Ch\u{01B0}a ho\u{00E0}n th\u{00E0}nh",
            ]);
        }
        fclose($out);
        exit;
    }

    private function baseData($userId)
    {
        return Cache::remember('dashboard-user-' . (int) $userId, 90, function () use ($userId) {
            $userLists = $this->listModel->getListsByUserId($userId);
            return [
                'userLists' => $userLists,
                'taskCounts' => $this->taskModel->getTaskCounts($userId, $userLists),
                'stats' => $this->taskModel->getStatistics($userId),
            ];
        });
    }
}
