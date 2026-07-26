@extends('layouts.app')

@section('content')

<div class="py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 m-0" style="color: var(--text-color);">{{ __('teams.title') }}</h2>
        <a href="/teams/create" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> {{ __('teams.create') }}
        </a>
    </div>

    <div class="row g-3">
        @if (count($teams) === 0)
            <div class="col-12">
                <div class="empty-state-vertical">
                    <div class="empty-icon"><i class="fa-solid fa-users-slash"></i></div>
                    <p class="empty-title">{{ __('teams.empty_title') }}</p>
                    <p class="empty-hint">{{ __('teams.empty_hint') }}</p>
                </div>
            </div>
        @else
            @foreach ($teams as $team)
                <div class="col-md-6 col-lg-4">
                    <div class="form-card" style="height: 100%; display: flex; flex-direction: column;">
                        <h3 style="margin: 0 0 6px 0; font-size: 1.1rem;">
                            <i class="fa-solid fa-users me-2 text-primary"></i>{{ $team->name }}
                        </h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem; flex-grow: 1; margin-bottom: 10px;">
                            {{ $team->description ?? __('teams.no_description') }}
                        </p>

                        <div style="border-top: 1px solid var(--border-color); padding-top: 10px; margin-bottom: 10px; font-size: 0.8rem; color: var(--text-muted);">
                            <div><i class="fa-solid fa-user-shield me-1"></i> {{ __('teams.owner') }}: <strong style="color: var(--text-color);">{{ $team->owner_name ?? 'N/A' }}</strong></div>
                            <div><i class="fa-solid fa-user-group me-1"></i> {{ __('teams.members', ['count' => (int)($team->total_members ?? 1)]) }}</div>
                            <div><i class="fa-solid fa-id-badge me-1"></i> {{ __('teams.role') }}:
                                <span class="priority-badge {{ $team->user_role === 'owner' ? 'priority-high' : ($team->user_role === 'admin' ? '' : 'priority-low') }}" style="font-size: 0.65rem;">
                                    {{ strtoupper($team->user_role ?? 'MEMBER') }}
                                </span>
                            </div>
                        </div>

                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <a href="/teams/detail?id={{ (int)$team->id }}" class="btn btn-secondary btn-sm" style="flex-grow: 1;">
                                {{ __('teams.view_details') }}
                            </a>

                            @if (($team->user_role ?? '') === 'owner')
                                <form action="/teams/delete" method="POST" onsubmit="return confirm(@js(__('teams.delete_confirm')));">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ (int)$team->id }}">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @else
                                <form action="/teams/leave" method="POST" onsubmit="return confirm(@js(__('teams.leave_confirm', ['name' => $team->name])));">
                                    @csrf
                                    <input type="hidden" name="team_id" value="{{ (int)$team->id }}">
                                <button type="submit" class="btn btn-danger btn-sm" title="{{ __('teams.leave') }}">
                                        <i class="fa-solid fa-right-from-bracket"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

@endsection
