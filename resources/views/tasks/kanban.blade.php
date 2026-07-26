@extends('layouts.app')

@php
$title = $title ?? __('kanban.title');
$labels = [
    'todo' => [__('kanban.todo'), 0, 'fa-list'],
    'doing' => [__('kanban.doing'), 50, 'fa-spinner'],
    'done' => [__('kanban.done'), 100, 'fa-circle-check'],
];
@endphp

@section('content')
<link rel="stylesheet" href="/css/kanban.css?v=20260727a">

<header class="content-header">
    <div class="header-left">
        <h1><i class="fa-solid fa-table-columns"></i> {{ __('kanban.title') }}</h1>
        <p class="current-date">{{ __('kanban.subtitle') }}</p>
    </div>
    <a href="/tasks/create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> {{ __('kanban.add_task') }}</a>
</header>

<p class="kanban-drag-hint"><i class="fa-solid fa-hand-pointer"></i> {{ __('kanban.drag_hint') }}</p>

<div class="kanban-board" data-kanban-board>
    @foreach ($labels as $key => [$label, $targetProgress, $icon])
        <section class="kanban-column kanban-{{ $key }}" data-progress="{{ $targetProgress }}">
            <header>
                <h2><i class="fa-solid {{ $icon }}"></i> {{ $label }}</h2>
                <span data-column-count>{{ count($columns[$key]) }}</span>
            </header>
            <div class="kanban-list" data-kanban-list>
                @if (empty($columns[$key]))
                    <p class="kanban-empty">{{ __('kanban.empty') }}</p>
                @endif

                @foreach ($columns[$key] as $task)
                    <article class="kanban-card" draggable="true" data-task-id="{{ (int) $task->id }}">
                        <a href="/tasks/edit?id={{ (int) $task->id }}" class="kanban-title">{{ $task->title }}</a>
                        <div class="kanban-meta">
                            <span class="priority-badge priority-{{ $task->priority ?? 'normal' }}">
                                {{ ($task->priority ?? 'normal') === 'high' ? __('kanban.high') : (($task->priority ?? 'normal') === 'low' ? __('kanban.low') : __('kanban.normal')) }}
                            </span>
                            @if (!empty($task->due_date))
                                <span><i class="fa-regular fa-calendar"></i> {{ $task->due_date->format('d/m/Y') }}</span>
                            @endif
                        </div>
                        <div class="task-progress"><span style="width: {{ (int) ($task->progress ?? 0) }}%"></span></div>
                        <div class="kanban-actions"><span>{{ (int) ($task->progress ?? 0) }}%</span></div>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</div>

<script src="/js/kanban.js?v=20260727a"></script>
@endsection
