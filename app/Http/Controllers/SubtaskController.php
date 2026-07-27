<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Subtask;
use App\Models\Task;

class SubtaskController extends Controller
{
    /** Số việc con tối đa cho một công việc. */
    const MAX_SUBTASKS = 20;

    public function store(Request $request)
    {
        $userId = auth()->id();
        $task = $this->accessibleTask($request->input('task_id'), $userId);
        $title = trim($request->input('title', ''));

        if (!$task) {
            session()->flash('error', 'Không tìm thấy công việc.');
            return redirect('/');
        }

        if ($title === '') {
            session()->flash('error', 'Hãy nhập tên việc con.');
            return $this->back($task);
        }

        if (mb_strlen($title) > 200) {
            session()->flash('error', 'Tên việc con tối đa 200 ký tự.');
            return $this->back($task);
        }

        if ($task->subtasks()->count() >= self::MAX_SUBTASKS) {
            session()->flash('error', 'Mỗi công việc tối đa ' . self::MAX_SUBTASKS . ' việc con.');
            return $this->back($task);
        }

        Subtask::create([
            'task_id'  => $task->id,
            'title'    => $title,
            'position' => (int) $task->subtasks()->max('position') + 1,
        ]);

        $task->refreshFromSubtasks();
        Cache::forget('dashboard-user-' . $userId);

        return $this->back($task);
    }

    public function toggle(Request $request)
    {
        $userId = auth()->id();
        $subtask = Subtask::find((int) $request->input('id', 0));
        $task = $subtask ? $this->accessibleTask($subtask->task_id, $userId) : null;

        if (!$subtask || !$task) {
            session()->flash('error', 'Không tìm thấy việc con.');
            return redirect('/');
        }

        $subtask->update(['completed' => !$subtask->completed]);
        $task->refreshFromSubtasks();
        Cache::forget('dashboard-user-' . $userId);

        return $this->back($task);
    }

    public function destroy(Request $request)
    {
        $userId = auth()->id();
        $subtask = Subtask::find((int) $request->input('id', 0));
        $task = $subtask ? $this->accessibleTask($subtask->task_id, $userId) : null;

        if (!$subtask || !$task) {
            session()->flash('error', 'Không tìm thấy việc con.');
            return redirect('/');
        }

        $subtask->delete();
        $task->refreshFromSubtasks();
        Cache::forget('dashboard-user-' . $userId);

        return $this->back($task);
    }

    /**
     * Task mà user được phép sửa: do mình tạo, được giao cho mình,
     * hoặc thuộc nhóm mà mình là thành viên.
     */
    private function accessibleTask($taskId, $userId): ?Task
    {
        if (!$taskId) return null;

        return Task::where('id', (int) $taskId)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('assignee_id', $userId)
                  ->orWhereIn('team_id', function ($sub) use ($userId) {
                      $sub->select('team_id')->from('team_members')->where('user_id', $userId);
                  });
            })
            ->first();
    }

    private function back(Task $task)
    {
        return redirect('/tasks/edit?id=' . (int) $task->id . '#subtasks');
    }
}
