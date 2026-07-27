<?php

namespace App\Http\Controllers;

use App\Services\NotificationCenter;

class NotificationController extends Controller
{
    /**
     * Dữ liệu cho chuông thông báo, để JS tự làm mới không cần tải lại trang.
     */
    public function feed()
    {
        $userId = auth()->id();
        $data = NotificationCenter::forUser($userId);

        return response()->json([
            'count' => $data['count'],
            'invitations' => $data['invitations']->map(fn($invitation) => [
                'id'      => (int) $invitation->id,
                'team'    => $invitation->team->name ?? '',
                'inviter' => $invitation->inviter->name ?? '',
                'role'    => $invitation->role,
                'ago'     => $invitation->created_at?->diffForHumans(),
            ])->values(),
            'overdue' => $data['overdue']->map(fn($task) => [
                'id'    => (int) $task->id,
                'title' => $task->title,
                'days'  => $task->due_date->diffInDays(today()),
                'due'   => $task->due_date->format('d/m/Y'),
            ])->values(),
            'overdue_total' => $data['overdueTotal'],
            'due_today' => $data['dueToday']->map(fn($task) => [
                'id'    => (int) $task->id,
                'title' => $task->title,
            ])->values(),
            'assigned' => $data['assigned']->map(fn($task) => [
                'id'       => (int) $task->id,
                'title'    => $task->title,
                'assigner' => $task->user->name ?? '',
            ])->values(),
        ]);
    }
}
