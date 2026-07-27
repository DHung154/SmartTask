@extends('layouts.app')

@php
$title = $title ?? __('kanban.title');
$icons = [
    'todo'   => 'fa-list',
    'doing'  => 'fa-spinner',
    'review' => 'fa-magnifying-glass',
    'done'   => 'fa-circle-check',
];
$statusLabels = $statusLabels ?? App\Models\Task::statusLabels();
@endphp

@section('content')
<link rel="stylesheet" href="/css/kanban.css?v=20260728a">

<header class="content-header">
    <div class="header-left">
        <h1><i class="fa-solid fa-table-columns"></i> {{ __('kanban.title') }}</h1>
        <p class="current-date">{{ __('kanban.subtitle') }}</p>
    </div>
    <a href="/tasks/create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> {{ __('kanban.add_task') }}</a>
</header>

<p class="kanban-drag-hint"><i class="fa-solid fa-hand-pointer"></i> {{ __('kanban.drag_hint') }}</p>

<div class="kanban-board" data-kanban-board>
    @foreach ($statusLabels as $key => $label)
        <section class="kanban-column kanban-{{ $key }}" data-status="{{ $key }}">
            <header>
                <h2><i class="fa-solid {{ $icons[$key] ?? 'fa-list' }}"></i> {{ $label }}</h2>
                <span data-column-count>{{ count($columns[$key]) }}</span>
            </header>
            <div class="kanban-list" data-kanban-list>
                <p class="kanban-empty" data-kanban-empty style="{{ count($columns[$key]) ? 'display:none;' : '' }}">
                    {{ __('kanban.empty') }}
                </p>

                @foreach ($columns[$key] as $task)
                    @php
                        $summary = $task->subtaskSummary();
                        $priority = $task->priority ?? 'normal';
                    @endphp
                    <article class="kanban-card" draggable="true" data-task-id="{{ (int) $task->id }}">
                        <a href="/tasks/edit?id={{ (int) $task->id }}" class="kanban-title">{{ $task->title }}</a>
                        <div class="kanban-meta">
                            <span class="priority-badge priority-{{ $priority }}">
                                {{ $priority === 'high' ? __('kanban.high') : ($priority === 'low' ? __('kanban.low') : __('kanban.normal')) }}
                            </span>
                            @if (!empty($task->due_date))
                                <span><i class="fa-regular fa-calendar"></i> {{ $task->due_date->format('d/m/Y') }}</span>
                            @endif
                            @if ($summary['total'] > 0)
                                <span><i class="fa-solid fa-list-check"></i> {{ $summary['done'] }}/{{ $summary['total'] }}</span>
                            @endif
                        </div>
                        @if ($task->assignee)
                            <div class="kanban-assignee">
                                <span class="avatar kanban-avatar">{{ mb_strtoupper(mb_substr($task->assignee->name, 0, 1)) }}</span>
                                <span>{{ $task->assignee->name }}</span>
                            </div>
                        @endif
                        <div class="task-progress"><span style="width: {{ (int) ($task->progress ?? 0) }}%" data-progress-bar></span></div>
                        <div class="kanban-actions"><span data-progress-text>{{ (int) ($task->progress ?? 0) }}%</span></div>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</div>

<div class="kanban-toast" data-kanban-toast hidden></div>

<script src="/js/kanban.js?v=20260728a"></script>
@endsection
