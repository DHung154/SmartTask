@php
    $notif = $notifications ?? App\Services\NotificationCenter::empty();
    $invitations = $notif['invitations'];
    $overdue = $notif['overdue'];
    $dueToday = $notif['dueToday'];
    $assigned = $notif['assigned'] ?? collect();
    $badge = (int) $notif['count'];
    $redirect = request()->getRequestUri();
@endphp

<div class="notif" id="notifBell" data-count="{{ $badge }}">
    <button type="button" class="icon-btn notif-btn" id="notifToggle"
            aria-haspopup="true" aria-expanded="false"
            title="{{ __('notif.title') }}" aria-label="{{ __('notif.title') }}">
        <i class="fa-solid fa-bell"></i>
        @if ($badge > 0)
            <span class="notif-badge">{{ $badge > 9 ? '9+' : $badge }}</span>
        @endif
    </button>

    <div class="notif-panel" role="menu" aria-label="{{ __('notif.title') }}">
        <div class="notif-head">
            <span><i class="fa-solid fa-bell me-1"></i> {{ __('notif.title') }}</span>
            @if ($badge > 0)
                <span class="notif-head-count">{{ $badge }}</span>
            @endif
        </div>

        <div class="notif-body">
            @if ($invitations->isNotEmpty())
                <div class="notif-section-title">{{ __('notif.invitations') }}</div>

                @foreach ($invitations as $invitation)
                    <div class="notif-item notif-item-invite">
                        <span class="notif-icon notif-icon-invite"><i class="fa-solid fa-user-plus"></i></span>
                        <div class="notif-content">
                            <p class="notif-text">
                                {!! __('notif.invited_you', [
                                    'inviter' => '<strong>' . e($invitation->inviter->name ?? __('notif.someone')) . '</strong>',
                                    'team' => '<strong>' . e($invitation->team->name ?? '') . '</strong>',
                                ]) !!}
                            </p>
                            <p class="notif-meta">
                                <span class="notif-role">{{ $invitation->role === 'admin' ? __('teams.role_admin') : __('teams.role_member') }}</span>
                                <span>{{ $invitation->created_at?->diffForHumans() }}</span>
                            </p>
                            <div class="notif-actions">
                                <form method="POST" action="/invitations/accept">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ (int) $invitation->id }}">
                                    <input type="hidden" name="redirect" value="{{ $redirect }}">
                                    <button type="submit" class="btn btn-primary btn-sm notif-accept">
                                        <i class="fa-solid fa-check me-1"></i>{{ __('notif.accept') }}
                                    </button>
                                </form>
                                <form method="POST" action="/invitations/decline">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ (int) $invitation->id }}">
                                    <input type="hidden" name="redirect" value="{{ $redirect }}">
                                    <button type="submit" class="btn btn-secondary btn-sm notif-decline">
                                        <i class="fa-solid fa-xmark me-1"></i>{{ __('notif.decline') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

            @if ($assigned->isNotEmpty())
                <div class="notif-section-title">
                    {{ __('notif.assigned') }}
                    <a href="/tasks?filter=assigned-to-me" class="notif-section-link">{{ __('notif.view_all') }}</a>
                </div>

                @foreach ($assigned as $task)
                    <a href="/tasks/edit?id={{ (int) $task->id }}" class="notif-item notif-item-link">
                        <span class="notif-icon notif-icon-assign"><i class="fa-solid fa-user-check"></i></span>
                        <div class="notif-content">
                            <p class="notif-text">{{ $task->title }}</p>
                            <p class="notif-meta">{{ __('notif.assigned_by', ['name' => $task->user->name ?? '']) }}</p>
                        </div>
                    </a>
                @endforeach
            @endif

            @if ($overdue->isNotEmpty())
                <div class="notif-section-title">
                    {{ __('notif.overdue') }}
                    <a href="/tasks?filter=overdue" class="notif-section-link">{{ __('notif.view_all') }}</a>
                </div>

                @foreach ($overdue as $task)
                    <a href="/tasks/edit?id={{ (int) $task->id }}" class="notif-item notif-item-link">
                        <span class="notif-icon notif-icon-danger"><i class="fa-solid fa-triangle-exclamation"></i></span>
                        <div class="notif-content">
                            <p class="notif-text">{{ $task->title }}</p>
                            <p class="notif-meta notif-meta-danger">
                                {{ __('notif.late_by', ['days' => $task->due_date->diffInDays(today())]) }}
                                · {{ $task->due_date->format('d/m/Y') }}
                            </p>
                        </div>
                    </a>
                @endforeach

                @if ($notif['overdueTotal'] > $overdue->count())
                    <a href="/tasks?filter=overdue" class="notif-more">
                        {{ __('notif.and_more', ['count' => $notif['overdueTotal'] - $overdue->count()]) }}
                    </a>
                @endif
            @endif

            @if ($dueToday->isNotEmpty())
                <div class="notif-section-title">{{ __('notif.due_today') }}</div>

                @foreach ($dueToday as $task)
                    <a href="/tasks/edit?id={{ (int) $task->id }}" class="notif-item notif-item-link">
                        <span class="notif-icon notif-icon-warning"><i class="fa-regular fa-clock"></i></span>
                        <div class="notif-content">
                            <p class="notif-text">{{ $task->title }}</p>
                            <p class="notif-meta">{{ __('notif.due_today_meta') }}</p>
                        </div>
                    </a>
                @endforeach
            @endif

            @if ($badge === 0)
                <div class="notif-empty">
                    <i class="fa-regular fa-bell-slash"></i>
                    <p>{{ __('notif.empty') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
