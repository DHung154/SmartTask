<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskComment;

class TaskCommentController extends Controller
{
    const MAX_LENGTH = 2000;

    public function store(Request $request)
    {
        $userId = auth()->id();
        $task = $this->accessibleTask($request->input('task_id'), $userId);
        $body = trim($request->input('body', ''));

        if (!$task) {
            session()->flash('error', 'Không tìm thấy công việc.');
            return redirect('/');
        }

        if ($body === '') {
            session()->flash('error', 'Hãy nhập nội dung bình luận.');
            return $this->back($task);
        }

        if (mb_strlen($body) > self::MAX_LENGTH) {
            session()->flash('error', 'Bình luận tối đa ' . self::MAX_LENGTH . ' ký tự.');
            return $this->back($task);
        }

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'body'    => $body,
        ]);

        ActivityLog::log($userId, 'comment', 'task', $task->id, 'Bình luận: ' . $task->title);

        return $this->back($task);
    }

    /**
     * Xoá bình luận: chỉ tác giả bình luận hoặc người tạo công việc.
     */
    public function destroy(Request $request)
    {
        $userId = auth()->id();
        $comment = TaskComment::find((int) $request->input('id', 0));
        $task = $comment ? $this->accessibleTask($comment->task_id, $userId) : null;

        if (!$comment || !$task) {
            session()->flash('error', 'Không tìm thấy bình luận.');
            return redirect('/');
        }

        if ((int) $comment->user_id !== $userId && (int) $task->user_id !== $userId) {
            session()->flash('error', 'Bạn chỉ xoá được bình luận của mình.');
            return $this->back($task);
        }

        $comment->delete();
        session()->flash('success', 'Đã xoá bình luận.');

        return $this->back($task);
    }

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
        return redirect('/tasks/edit?id=' . (int) $task->id . '#comments');
    }
}
