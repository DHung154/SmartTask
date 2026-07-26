@extends('layouts.app')

@php
$team = $team ?? [];
$myRole = $myRole ?? 'member';
$members = $members ?? [];
$tasks = $tasks ?? [];
$teamId = $team['id'] ?? 0;
$isOwner = $myRole === 'owner';
$isAdmin = $myRole === 'admin';
$canManage = $isOwner || $isAdmin;
$firebaseConfig = config('services.firebase', []);
$firebaseReady = !empty($firebaseConfig['api_key'])
    && !empty($firebaseConfig['auth_domain'])
    && !empty($firebaseConfig['project_id'])
    && !empty($firebaseConfig['storage_bucket'])
    && !empty($firebaseConfig['app_id']);
$teamChatConfig = [
    'enabled' => $firebaseReady,
    'teamId' => (int) $teamId,
    'teamName' => (string) ($team->name ?? ''),
    'userId' => (int) auth()->id(),
    'userName' => (string) (auth()->user()->name ?? __('teams.role_member')),
    'locale' => app()->getLocale(),
    'firebaseConfig' => [
        'apiKey' => $firebaseConfig['api_key'] ?? '',
        'authDomain' => $firebaseConfig['auth_domain'] ?? '',
        'projectId' => $firebaseConfig['project_id'] ?? '',
        'databaseURL' => $firebaseConfig['database_url'] ?? '',
        'storageBucket' => $firebaseConfig['storage_bucket'] ?? '',
        'messagingSenderId' => $firebaseConfig['messaging_sender_id'] ?? '',
        'appId' => $firebaseConfig['app_id'] ?? '',
        'measurementId' => $firebaseConfig['measurement_id'] ?? '',
    ],
    'labels' => [
        'member' => __('teams.role_member'),
        'no_messages' => __('chat.no_messages'),
        'attachment_image' => __('chat.attachment_image'),
        'attachment_file' => __('chat.attachment_file'),
        'firebase_missing' => __('chat.firebase_missing'),
        'connected' => __('chat.connected'),
        'sync_error' => __('chat.sync_error'),
        'file_too_large' => __('chat.file_too_large'),
        'uploading' => __('chat.uploading'),
        'sending' => __('chat.sending'),
        'sent' => __('chat.sent'),
        'storage_error' => __('chat.storage_error'),
        'database_error' => __('chat.database_error'),
        'firebase_error' => __('chat.firebase_error'),
        'connection_error' => __('chat.connection_error'),
    ],
];
@endphp

@section('content')

<link rel="stylesheet" href="/css/team-chat.css?v=20260727b">

<div class="container-fluid py-4 px-4">
    <!-- Header trang nhóm -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="/teams" class="btn btn-secondary btn-sm mb-3">
                <i class="fa-solid fa-arrow-left me-1"></i> {{ __('teams.back') }}
            </a>
            <div class="form-card" style="position: relative;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h1 style="margin: 0 0 6px 0; font-size: 1.5rem;">
                            <i class="fa-solid fa-users me-2 text-primary"></i>{{ $team->name ?? '' }}
                        </h1>
                        <p style="color: var(--text-muted); margin: 0;">{{ $team->description ?? __('teams.no_description') }}</p>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        @if ($canManage)
                            <a href="/teams/edit?id={{ $teamId }}" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-pen me-1"></i> {{ __('teams.edit') }}
                            </a>
                        @endif
                        @if (!$isOwner)
                            <form action="/teams/leave" method="POST" onsubmit="return confirm(@js(__('teams.leave_current_confirm')));">
                                @csrf
                                <input type="hidden" name="team_id" value="{{ $teamId }}">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fa-solid fa-right-from-bracket me-1"></i> {{ __('teams.leave_current') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <div style="margin-top: 10px; display: flex; gap: 15px; color: var(--text-muted); font-size: 0.85rem;">
                    <span><i class="fa-solid fa-user-shield me-1"></i> {{ __('teams.role') }}: <strong style="color: var(--text-color);">{{ $myRole === 'owner' ? __('teams.role_owner') : ($myRole === 'admin' ? __('teams.role_admin') : __('teams.role_member')) }}</strong></span>
                    <span><i class="fa-solid fa-user-group me-1"></i> {{ __('teams.members', ['count' => count($members)]) }}</span>
                    <span><i class="fa-solid fa-list-check me-1"></i> {{ __('teams.tasks', ['count' => count($tasks)]) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Cột trái: Danh sách công việc -->
        <div class="col-lg-8">
            <div class="form-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h2 class="section-title" style="margin: 0;">
                        <i class="fa-solid fa-list-check me-2"></i>{{ __('teams.task_title') }}
                    </h2>
                    <span class="priority-badge priority-normal">{{ __('teams.tasks', ['count' => count($tasks)]) }}</span>
                </div>

                @if (count($tasks) > 0)
                    @foreach ($tasks as $task)
                        <div class="task-item" style="margin-bottom: 8px;">
                            <a href="/tasks/edit?id={{ (int)$task->id }}" class="task-content-link">
                                <div class="task-content">
                                    <span class="task-title">{{ $task->title }}</span>
                                    <div class="task-meta">
                                        <span class="meta-note"><i class="fa-regular fa-user"></i> {{ $task->author_name }}</span>
                                        @if ($task->priority !== 'normal')
                                            <span class="priority-badge priority-{{ $task->priority }}">
                                                {{ $task->priority === 'high' ? __('teams.priority_high') : __('teams.priority_low') }}
                                            </span>
                                        @endif
                                        <span class="meta-progress">
                                            <i class="fa-solid fa-chart-line"></i> {{ (int)$task->progress }}%
                                        </span>
                                        @if ($task->completed)
                                            <span class="priority-badge" style="background: var(--success-color); color: #fff;">{{ __('teams.completed') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                @else
                    <div class="empty-state-vertical">
                        <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                        <p class="empty-title">{{ __('teams.empty_tasks') }}</p>
                        <p class="empty-hint">{{ __('teams.empty_tasks_hint') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Cột phải: Mời thành viên & Danh sách thành viên -->
        <div class="col-lg-4 d-flex flex-column gap-4">

            <!-- Khối Mời thành viên -->
            @if ($canManage)
                <div class="form-card">
                    <h2 class="section-title">
                        <i class="fa-solid fa-user-plus me-2 text-success"></i>{{ __('teams.invite') }}
                    </h2>
                    <form action="/teams/add-member" method="POST">
                        @csrf
                        <input type="hidden" name="team_id" value="{{ $teamId }}">

                        <div class="form-group">
                            <label for="email">{{ __('teams.email') }}</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="nhap@email.com" required>
                        </div>

                        <div class="form-group">
                            <label for="role">{{ __('teams.role') }}</label>
                            <select name="role" id="role" class="form-control">
                                <option value="member">{{ __('teams.role_member') }}</option>
                                <option value="admin">{{ __('teams.role_admin') }}</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fa-solid fa-paper-plane me-1"></i> {{ __('teams.add') }}
                        </button>
                    </form>
                </div>
            @endif

            <!-- Khối Danh sách thành viên -->
            <div class="form-card">
                <h2 class="section-title">
                    <i class="fa-solid fa-users me-2"></i>{{ __('teams.members_title', ['count' => count($members)]) }}
                </h2>

                @foreach ($members as $member)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                        <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                            <span class="avatar" style="width: 36px; height: 36px; font-size: 0.85rem;">{{ mb_strtoupper(mb_substr($member->name ?? 'U', 0, 1)) }}</span>
                            <div style="min-width: 0;">
                                <div style="font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $member->name ?? '' }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $member->email ?? '' }}</div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                            <!-- Badge vai trò -->
                            @php
                                $roleBg = $member->role === 'owner' ? 'var(--danger-color)' : ($member->role === 'admin' ? '#e6a700' : 'var(--text-muted)');
                                $roleLabel = $member->role === 'owner' ? 'OWNER' : ($member->role === 'admin' ? 'ADMIN' : 'MEMBER');
                            @endphp
                            <span class="priority-badge" style="background: {{ $roleBg }}; color: #fff; font-size: 0.65rem;">{{ $roleLabel }}</span>

                            <!-- Nút hành động -->
                            @if ($member->role !== 'owner' && $member->user_id != auth()->id())
                                {{-- Đổi vai trò (chỉ owner) --}}
                                @if ($isOwner)
                                    <form action="/teams/change-role" method="POST" style="margin: 0;">
                                        @csrf
                                        <input type="hidden" name="team_id" value="{{ $teamId }}">
                                        <input type="hidden" name="user_id" value="{{ $member->user_id }}">
                                        <input type="hidden" name="role" value="{{ $member->role === 'admin' ? 'member' : 'admin' }}">
                                        <button type="submit" class="btn btn-secondary btn-sm" title="{{ $member->role === 'admin' ? __('teams.demote') : __('teams.promote') }}" style="padding: 2px 8px; font-size: 0.7rem;">
                                            <i class="fa-solid fa-{{ $member->role === 'admin' ? 'arrow-down' : 'arrow-up' }}"></i>
                                        </button>
                                    </form>

                                    {{-- Chuyển quyền chủ nhóm --}}
                                    <form action="/teams/transfer" method="POST" style="margin: 0;" onsubmit="return confirm(@js(__('teams.transfer_confirm', ['name' => $member->name])));">
                                        @csrf
                                        <input type="hidden" name="team_id" value="{{ $teamId }}">
                                        <input type="hidden" name="user_id" value="{{ $member->user_id }}">
                                        <button type="submit" class="btn btn-secondary btn-sm" title="{{ __('teams.transfer') }}" style="padding: 2px 8px; font-size: 0.7rem;">
                                            <i class="fa-solid fa-crown"></i>
                                        </button>
                                    </form>
                                @endif

                                {{-- Xóa thành viên (owner hoặc admin kick member) --}}
                                @if ($canManage && !($isAdmin && $member->role === 'admin'))
                                    <form action="/teams/remove-member" method="POST" style="margin: 0;" onsubmit="return confirm(@js(__('teams.remove_confirm', ['name' => $member->name])));">
                                        @csrf
                                        <input type="hidden" name="team_id" value="{{ $teamId }}">
                                        <input type="hidden" name="user_id" value="{{ $member->user_id }}">
                                        <button type="submit" class="btn btn-danger btn-sm" title="{{ __('teams.remove') }}" style="padding: 2px 8px; font-size: 0.7rem;">
                                            <i class="fa-solid fa-user-xmark"></i>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Khối Xóa nhóm (chỉ Owner) -->
            @if ($isOwner)
                <div class="form-card" style="border-color: var(--danger-color);">
                    <h2 class="section-title" style="color: var(--danger-color);">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ __('teams.warning') }}
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 12px;">
                        {{ __('teams.delete_warning') }}
                    </p>
                    <form action="/teams/delete" method="POST" onsubmit="return confirm(@js(__('teams.delete_confirm')));">
                        @csrf
                        <input type="hidden" name="id" value="{{ $teamId }}">
                        <button type="submit" class="btn btn-danger" style="width: 100%;">
                            <i class="fa-solid fa-trash me-1"></i> {{ __('teams.delete') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <section class="team-chat-card" data-team-chat aria-labelledby="team-chat-title">
        <div class="team-chat-header">
            <div>
                <h2 class="team-chat-title" id="team-chat-title">
                    <i class="fa-solid fa-comments me-2 text-primary"></i>{{ __('chat.title') }}
                </h2>
                <p class="team-chat-subtitle">{{ __('chat.subtitle') }}</p>
            </div>
            <span class="priority-badge priority-normal">Firebase</span>
        </div>

        <div class="team-chat-status" data-chat-status>{{ __('chat.initializing') }}</div>

        @if (!$firebaseReady)
            <div class="team-chat-setup">{{ __('chat.setup') }}</div>
        @endif

        <div class="team-chat-messages" data-chat-messages aria-live="polite">
            <div class="team-chat-empty">{{ __('chat.loading') }}</div>
        </div>

        <form class="team-chat-form" data-chat-form>
            <label class="team-chat-file-button" title="{{ __('chat.attach') }}">
                <i class="fa-solid fa-paperclip"></i>
                <input type="file" data-chat-file accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt">
            </label>
            <span class="team-chat-file-name" data-chat-file-name></span>
            <textarea class="form-control team-chat-input" data-chat-input rows="1" maxlength="2000" placeholder="{{ __('chat.placeholder') }}"></textarea>
            <button type="submit" class="btn btn-primary" data-chat-submit>
                <i class="fa-solid fa-paper-plane me-1"></i> {{ __('chat.send') }}
            </button>
        </form>
    </section>
</div>

<script type="application/json" id="team-chat-config">@json($teamChatConfig)</script>
<script type="module" src="/js/team-chat.js?v=20260727f"></script>

@endsection
