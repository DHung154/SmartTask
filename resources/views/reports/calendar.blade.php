@extends('layouts.app')

@php
$title = $title ?? "L\u{1ECB}ch deadline";
$month = $month ?? date('Y-m');
$tasksByDay = [];
foreach ($tasks ?? [] as $task) {
    $dateKey = $task->due_date ? $task->due_date->toDateString() : null;
    if ($dateKey) { $tasksByDay[$dateKey][] = $task; }
}
$busyDays = $tasksByDay;
uksort($busyDays, fn($a, $b) => strcmp($a, $b));
$maxTasksInDay = 1;
foreach ($busyDays as $items) { $maxTasksInDay = max($maxTasksInDay, count($items)); }
$firstDay = strtotime($month . '-01');
$daysInMonth = (int) date('t', $firstDay);
$startWeekday = (int) date('N', $firstDay);
$prevMonth = date('Y-m', strtotime('-1 month', $firstDay));
$nextMonth = date('Y-m', strtotime('+1 month', $firstDay));
$selectedDate = $selectedDate ?? '';
$selectedTasks = $selectedTasks ?? [];
@endphp

@section('content')
<header class="content-header">
    <div class="header-left">
        <h1><i class="fa-regular fa-calendar"></i> Lịch deadline</h1>
        <p class="current-date">Theo dõi công việc đến hạn trong tháng {{ date('m/Y', $firstDay) }}</p>
    </div>
    <div class="header-right">
        <a href="/calendar?month={{ $prevMonth }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-chevron-left"></i></a>
        <a href="/calendar?month={{ date('Y-m') }}" class="btn btn-secondary btn-sm">Tháng này</a>
        <a href="/calendar?month={{ $nextMonth }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-chevron-right"></i></a>
    </div>
</header>

<section class="calendar-overview">
    <div>
        <h2>Sơ đồ việc trong tháng</h2>
        <p>Ngày nào nhiều nhiệm vụ sẽ có cột cao hơn. Trong ô ngày sẽ hiển từng nhiệm vụ, kể cả khi có 2-3 việc.</p>
    </div>
    <div class="workload-list">
        @if (empty($busyDays))
            <span class="empty-hint workload-empty">Tháng này chưa có deadline.</span>
        @else
            @foreach ($busyDays as $date => $items)
                @php $count = count($items); @endphp
                <a href="/calendar?month={{ $month }}&amp;day={{ $date }}#day-{{ $date }}" class="workload-item">
                    <span class="workload-item-date">{{ date('d/m', strtotime($date)) }}</span>
                    <span class="workload-item-titles">{{ implode(', ', array_map(fn($t) => $t->title, $items)) }}</span>
                    <span class="workload-item-count">{{ $count }} việc</span>
                </a>
            @endforeach
        @endif
    </div>
</section>

<div class="calendar-grid">
    @foreach (['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'] as $label)<div class="calendar-weekday">{{ $label }}</div>@endforeach
    @for ($i = 1; $i < $startWeekday; $i++)<div class="calendar-day muted"></div>@endfor
    @for ($day = 1; $day <= $daysInMonth; $day++)
        @php $date = $month . '-' . str_pad((string)$day, 2, '0', STR_PAD_LEFT); $dayTasks = $tasksByDay[$date] ?? []; @endphp
        <div id="day-{{ $date }}" class="calendar-day {{ $date === date('Y-m-d') ? 'today' : '' }} {{ $dayTasks ? 'has-tasks' : '' }} {{ $date === $selectedDate ? 'selected' : '' }}">
            <div class="calendar-day-head"><a href="/calendar?month={{ $month }}&amp;day={{ $date }}#day-{{ $date }}" class="calendar-date">{{ $day }}</a>@if ($dayTasks)<span class="calendar-count">{{ count($dayTasks) }} nhiệm vụ</span>@endif</div>
            <div class="calendar-tasks">
                @foreach (array_slice($dayTasks, 0, 4) as $task)<a href="/tasks/edit?id={{ (int)$task->id }}" class="calendar-task priority-{{ $task->priority ?? 'normal' }}"><span class="task-dot"></span><span>{{ $task->title }}</span></a>@endforeach
                @if (count($dayTasks) > 4)<span class="calendar-more">+{{ count($dayTasks) - 4 }} việc nữa</span>@endif
            </div>
        </div>
    @endfor
</div>
@if ($selectedDate !== '')
    <section class="calendar-detail" id="selected-day">
        <div><h2>Việc ngày {{ date('d/m/Y', strtotime($selectedDate)) }}</h2><p>{{ count($selectedTasks) }} nhiệm vụ có deadline trong ngày này.</p></div>
        <div class="calendar-detail-list">
            @if (empty($selectedTasks))<p class="empty-hint">Không có công việc trong ngày này.</p>
            @else
                @foreach ($selectedTasks as $task)<a href="/tasks/edit?id={{ (int)$task->id }}" class="calendar-detail-task priority-{{ $task->priority ?? 'normal' }}"><span>{{ $task->title }}</span><small>{{ !empty($task->completed) ? 'Đã hoàn thành' : 'Chưa hoàn thành' }}</small></a>@endforeach
            @endif
        </div>
    </section>
@endif
@endsection
