<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TeamInvitation;

/**
 * Gom dữ liệu cho chuông thông báo trên thanh điều hướng:
 * - Lời mời vào nhóm đang chờ trả lời (có nút chấp nhận / từ chối)
 * - Công việc quá hạn và đến hạn hôm nay
 */
class NotificationCenter
{
    /** Số task quá hạn / đến hạn hiển thị tối đa trong dropdown. */
    const TASK_LIMIT = 5;

    public static function forUser($userId): array
    {
        $invitations = TeamInvitation::pendingFor($userId);

        $overdue = Task::forUser($userId)
            ->where('completed', false)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->orderBy('due_date')
            ->limit(self::TASK_LIMIT)
            ->get(['id', 'title', 'due_date']);

        $dueToday = Task::forUser($userId)
            ->where('completed', false)
            ->whereDate('due_date', today())
            ->orderBy('title')
            ->limit(self::TASK_LIMIT)
            ->get(['id', 'title', 'due_date']);

        // Việc người khác giao cho mình mà chưa xong.
        $assigned = Task::where('assignee_id', $userId)
            ->where('user_id', '!=', $userId)
            ->where('completed', false)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(self::TASK_LIMIT)
            ->get(['id', 'title', 'user_id']);

        return [
            'invitations'  => $invitations,
            'overdue'      => $overdue,
            'dueToday'     => $dueToday,
            'assigned'     => $assigned,
            'overdueTotal' => Task::forUser($userId)
                ->where('completed', false)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', today())
                ->count(),
            'count'        => $invitations->count() + $overdue->count() + $dueToday->count() + $assigned->count(),
        ];
    }

    /** Giá trị rỗng dùng cho khách chưa đăng nhập. */
    public static function empty(): array
    {
        return [
            'invitations'  => collect(),
            'overdue'      => collect(),
            'dueToday'     => collect(),
            'assigned'     => collect(),
            'overdueTotal' => 0,
            'count'        => 0,
        ];
    }
}
