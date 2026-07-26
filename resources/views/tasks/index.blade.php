@extends('layouts.app')

@php
use App\Models\Task;

$title = $title ?? "C\u{00F4}ng vi\u{1EC7}c";

$filter     = $active_filter ?? 'inbox';
$sort       = $sort ?? 'smart';
$page       = $page ?? 1;
$totalPages = $totalPages ?? 1;
$totalTasks = $totalTasks ?? count($tasks ?? []);
$isTrash    = ($filter === 'trash');
$isSearch   = ($filter === 'search');
$search_query = $search_query ?? '';
$backUrl = request()->fullUrl();

$buildUrl = function (array $overrides = []) use ($filter, $sort, $page, $isSearch, $search_query) {
    $params = ['sort' => $sort, 'page' => $page];

    if ($isSearch) {
        $base = '/tasks/search';
        $params['q'] = $search_query ?? '';
    } else {
        $base = '/tasks';
        if (is_numeric($filter)) {
            $params['list'] = $filter;
        } else {
            $params['filter'] = $filter;
        }
    }

    return $base . '?' . http_build_query(array_merge($params, $overrides));
};
@endphp

@section('content')

<header class="content-header">
    <div class="header-left">
        <div class="header-title-row">
            <h1>{{ $title }}</h1>

            @if (!empty($currentList))
                <div class="list-actions">
                    <a href="/lists/edit?id={{ (int)$currentList['id'] }}" class="btn-icon" title="Đổi tên danh sách">
                        <i class="fa-solid fa-pen"></i>
                    </a>

                    <form method="POST" action="/lists/delete" class="inline-form"
                          onsubmit="return confirm('Xoa danh sach nay? Cac cong viec ben trong se duoc chuyen ve muc Cong viec.');">
                        @csrf
                        <input type="hidden" name="id" value="{{ (int)$currentList['id'] }}">
                        <button type="submit" class="btn-icon text-danger" title="Xóa danh sách">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
            @endif
        </div>
        <p class="current-date">{{ date('d/m/Y') }} &middot; {{ (int)$totalTasks }} công việc</p>
    </div>

    <div class="header-right">
        @if (!$isTrash && $totalTasks > 1)
            <form method="GET" action="{{ $isSearch ? '/tasks/search' : '/tasks' }}" class="sort-form">
                @if ($isSearch)
                    <input type="hidden" name="q" value="{{ $search_query ?? '' }}">
                @elseif (is_numeric($filter))
                    <input type="hidden" name="list" value="{{ (int)$filter }}">
                @else
                    <input type="hidden" name="filter" value="{{ $filter }}">
                @endif

                <label for="sort" class="sort-label"><i class="fa-solid fa-arrow-down-wide-short"></i></label>
                <select name="sort" id="sort" class="sort-select" onchange="this.form.submit()">
                    @foreach (Task::sortLabels() as $key => $label)
                        <option value="{{ $key }}" {{ $sort === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif

        @if ($isTrash && $totalTasks > 0)
            <form method="POST" action="/tasks/empty-trash" class="inline-form"
                  onsubmit="return confirm('Xoa vinh vien toan bo cong viec trong thung rac? Khong the hoan tac.');">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-trash-can"></i> Dọn sạch thùng rác
                </button>
            </form>
        @endif
    </div>
</header>

@if (!$isTrash && !$isSearch && !empty($stats) && $stats['total'] > 0)
    <div class="stats-strip">
        <a href="/tasks?filter=all" class="stat-chip">
            <span class="stat-chip-value">{{ (int)$stats['total'] }}</span>
            <span class="stat-chip-label">Tổng số</span>
        </a>
        <a href="/tasks?filter=completed" class="stat-chip stat-done">
            <span class="stat-chip-value">{{ (int)$stats['completed'] }}</span>
            <span class="stat-chip-label">Hoàn thành</span>
        </a>
        <a href="/tasks?filter=incomplete" class="stat-chip stat-todo">
            <span class="stat-chip-value">{{ (int)$stats['incomplete'] }}</span>
            <span class="stat-chip-label">Còn lại</span>
        </a>
        <a href="/tasks?filter=overdue" class="stat-chip {{ $stats['overdue'] > 0 ? 'stat-overdue' : '' }}">
            <span class="stat-chip-value">{{ (int)$stats['overdue'] }}</span>
            <span class="stat-chip-label">Quá hạn</span>
        </a>
        <a href="/tasks?filter=important" class="stat-chip stat-important">
            <span class="stat-chip-value">{{ (int)$stats['important'] }}</span>
            <span class="stat-chip-label">Quan trọng</span>
        </a>

        <div class="stat-progress" title="Tỷ lệ hoàn thành">
            <div class="stat-progress-bar">
                <div class="stat-progress-fill" style="width: {{ (float)$stats['completion_rate'] }}%"></div>
            </div>
            <span class="stat-progress-text">{{ (float)$stats['completion_rate'] }}%</span>
        </div>
    </div>
@endif

<div class="task-list-container">
    @if (!$isTrash && !$isSearch)
        <form method="POST" action="/tasks/quick-add" class="quick-add-form">
            @csrf
            <input type="hidden" name="redirect" value="{{ $backUrl }}">
            <input type="hidden" name="filter" value="{{ $filter }}">

            <div class="quick-add-icon"><i class="fa-solid fa-plus"></i></div>
            <input type="text" name="title" class="quick-add-input" maxlength="200"
                   placeholder="Thêm công việc - gõ rồi nhấn Enter" required>
            <button type="submit" class="quick-add-btn">Thêm</button>
            <a href="/tasks/create{{ is_numeric($filter) ? '?list=' . (int)$filter : '' }}"
               class="quick-add-more" title="Thêm với đầy đủ tùy chọn">
                <i class="fa-solid fa-sliders"></i>
            </a>
        </form>
    @endif

    @if (empty($tasks))
        <div class="empty-state-vertical">
            <div class="empty-icon">
                <i class="fa-solid {{ $isTrash ? 'fa-trash-can' : ($isSearch ? 'fa-magnifying-glass' : 'fa-clipboard-check') }}"></i>
            </div>
            <p class="empty-title">{{ $emptyText ?? "Ch\u{01B0}a c\u{00F3} c\u{00F4}ng vi\u{1EC7}c n\u{00E0}o \u{1EDF} \u{0111}\u{00E2}y." }}</p>

            @if ($isSearch)
                <a href="/" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Về danh sách
                </a>
            @elseif (!$isTrash)
                <p class="empty-hint">Dùng ô "Thêm công việc" ở trên để bắt đầu.</p>
            @endif
        </div>
    @else
        @foreach ($tasks as $task)
            @php
            $isCompleted = !empty($task->completed);
            $isImportant = !empty($task->is_important);
            $isOverdue = false;
            $dueLabel = '';

            if (!empty($task->due_date)) {
                $dueTs = strtotime($task->due_date);
                $todayTs = strtotime(date('Y-m-d'));
                $isOverdue = ($dueTs < $todayTs && !$isCompleted);

                if ($dueTs === $todayTs) {
                    $dueLabel = "H\u{00F4}m nay";
                } elseif ($dueTs === strtotime('+1 day', $todayTs)) {
                    $dueLabel = "Ng\u{00E0}y mai";
                } elseif ($dueTs === strtotime('-1 day', $todayTs)) {
                    $dueLabel = "H\u{00F4}m qua";
                } else {
                    $dueLabel = date('d/m/Y', $dueTs);
                }
            }
            @endphp

            <div class="task-item {{ $isCompleted ? 'completed' : '' }} {{ $isImportant ? 'is-important' : '' }}">
                @if ($isTrash)
                    <div class="task-checkbox-wrapper is-static">
                        <div class="custom-checkbox trashed"><i class="fa-solid fa-trash"></i></div>
                    </div>
                @else
                    <form method="POST" action="/tasks/toggle" class="inline-form">
                        @csrf
                        <input type="hidden" name="id" value="{{ (int)$task->id }}">
                        <input type="hidden" name="redirect" value="{{ $backUrl }}">
                        <button type="submit" class="task-checkbox-wrapper"
                                title="{{ $isCompleted ? 'Bỏ đánh dấu hoàn thành' : 'Đánh dấu hoàn thành' }}">
                            <span class="custom-checkbox">
                                @if ($isCompleted)<i class="fa-solid fa-check"></i>@endif
                            </span>
                        </button>
                    </form>
                @endif

                @if ($isTrash)
                    <div class="task-content-link is-static">
                        <div class="task-content">
                            <span class="task-title">{{ $task->title }}</span>
                            <div class="task-meta">
                                <span class="meta-deleted">
                                    <i class="fa-regular fa-clock"></i>
                                    Đã xóa {{ date('d/m/Y H:i', strtotime($task->deleted_at)) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="/tasks/edit?id={{ (int)$task->id }}" class="task-content-link">
                        <div class="task-content">
                            <span class="task-title">{{ $task->title }}</span>

                            <div class="task-meta">
                                @if (!empty($task->description))
                                    <span class="meta-note" title="Có ghi chú">
                                        <i class="fa-regular fa-note-sticky"></i>
                                    </span>
                                @endif

                                @if (($task->priority ?? 'normal') !== 'normal')
                                    <span class="priority-badge priority-{{ $task->priority }}">
                                        {{ ($task->priority === 'high') ? 'Ưu tiên cao' : 'Ưu tiên thấp' }}
                                    </span>
                                @endif

                                @if (!empty($task->attachment_path))
                                    <span class="attachment-link" title="{{ $task->attachment_name ?? "File \u{0111}\u{00ED}nh k\u{00E8}m" }}">
                                        <i class="fa-solid fa-paperclip"></i>
                                    </span>
                                @endif

                                @if ($dueLabel !== '')
                                    <span class="meta-date {{ $isOverdue ? 'text-danger' : '' }}">
                                        <i class="fa-regular fa-calendar"></i> {{ $dueLabel }}
                                        {{ $isOverdue ? '(quá hạn)' : '' }}
                                    </span>
                                @endif
                                <span class="meta-progress"><i class="fa-solid fa-chart-line"></i> {{ (int)($task->progress ?? ($isCompleted ? 100 : 0)) }}%</span>
                            </div>
                        </div>
                    </a>
                @endif

                <div class="task-actions-group">
                    @if ($isTrash)
                        <form method="POST" action="/tasks/restore" class="inline-form">
                            @csrf
                            <input type="hidden" name="id" value="{{ (int)$task->id }}">
                            <input type="hidden" name="redirect" value="{{ $backUrl }}">
                            <button type="submit" class="action-btn restore-btn" title="Khôi phục">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                        </form>

                        <form method="POST" action="/tasks/force-delete" class="inline-form"
                              onsubmit="return confirm('Xoa vinh vien cong viec nay? Khong the hoan tac.');">
                            @csrf
                            <input type="hidden" name="id" value="{{ (int)$task->id }}">
                            <input type="hidden" name="redirect" value="{{ $backUrl }}">
                            <button type="submit" class="action-btn delete-btn" title="Xóa vĩnh viễn">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </form>
                    @else
                        <form method="POST" action="/tasks/star" class="inline-form">
                            @csrf
                            <input type="hidden" name="id" value="{{ (int)$task->id }}">
                            <input type="hidden" name="redirect" value="{{ $backUrl }}">
                            <button type="submit" class="action-btn star-btn {{ $isImportant ? 'active' : '' }}"
                                    title="{{ $isImportant ? 'Bỏ đánh dấu quan trọng' : 'Đánh dấu quan trọng' }}">
                                <i class="{{ $isImportant ? 'fa-solid' : 'fa-regular' }} fa-star"></i>
                            </button>
                        </form>

                        <form method="POST" action="/tasks/delete" class="inline-form"
                              onsubmit="return confirm('Chuyen cong viec nay vao thung rac?');">
                            @csrf
                            <input type="hidden" name="id" value="{{ (int)$task->id }}">
                            <input type="hidden" name="redirect" value="{{ $backUrl }}">
                            <button type="submit" class="action-btn delete-btn" title="Chuyển vào thùng rác">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    @if ($totalPages > 1)
        <nav class="pagination" aria-label="Phân trang">
            @if ($page > 1)
                <a href="{{ $buildUrl(['page' => $page - 1]) }}" class="page-btn">
                    <i class="fa-solid fa-chevron-left"></i> Trước
                </a>
            @else
                <span class="page-btn disabled"><i class="fa-solid fa-chevron-left"></i> Trước</span>
            @endif

            @php
            $start = max(1, $page - 2);
            $end = min($totalPages, $start + 4);
            $start = max(1, $end - 4);
            @endphp

            @if ($start > 1)
                <a href="{{ $buildUrl(['page' => 1]) }}" class="page-num">1</a>
                @if ($start > 2)<span class="page-gap">&hellip;</span>@endif
            @endif

            @for ($i = $start; $i <= $end; $i++)
                @if ($i === $page)
                    <span class="page-num active">{{ $i }}</span>
                @else
                    <a href="{{ $buildUrl(['page' => $i]) }}" class="page-num">{{ $i }}</a>
                @endif
            @endfor

            @if ($end < $totalPages)
                @if ($end < $totalPages - 1)<span class="page-gap">&hellip;</span>@endif
                <a href="{{ $buildUrl(['page' => $totalPages]) }}" class="page-num">{{ (int)$totalPages }}</a>
            @endif

            @if ($page < $totalPages)
                <a href="{{ $buildUrl(['page' => $page + 1]) }}" class="page-btn">
                    Sau <i class="fa-solid fa-chevron-right"></i>
                </a>
            @else
                <span class="page-btn disabled">Sau <i class="fa-solid fa-chevron-right"></i></span>
            @endif
        </nav>

        <p class="pagination-info">Trang {{ (int)$page }} / {{ (int)$totalPages }} - tổng {{ (int)$totalTasks }} công việc</p>
    @endif
</div>

@endsection
