@php
    $priorityLabel = match ($task->priority) {
        'high' => 'Cao',
        'low' => 'Thấp',
        default => 'Bình thường',
    };
@endphp
<h2>Bạn vừa được giao một công việc</h2>

<p>Chào {{ $assignee->name ?? 'bạn' }},</p>

<p>
    <strong>{{ $assigner->name ?? 'Một thành viên' }}</strong> đã giao cho bạn công việc
    <strong>{{ $task->title }}</strong>.
</p>

<ul style="color: #292827;">
    <li>Mức ưu tiên: <strong>{{ $priorityLabel }}</strong></li>
    @if ($task->due_date)
        <li>Hạn chót: <strong>{{ $task->due_date->format('d/m/Y') }}</strong></li>
    @endif
    @if ($task->team)
        <li>Nhóm: <strong>{{ $task->team->name }}</strong></li>
    @endif
</ul>

@if (!empty($task->description))
    <p style="color: #605e5c;">{{ $task->description }}</p>
@endif

<p>
    <a href="{{ rtrim(config('app.url'), '/') }}/tasks/edit?id={{ (int) $task->id }}"
       style="display: inline-block; background: #7b68ee; color: #ffffff; padding: 10px 18px; border-radius: 6px; text-decoration: none;">
        Xem công việc
    </a>
</p>
