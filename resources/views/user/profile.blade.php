@extends('layouts.app')

@php
$title = $title ?? "Tài khoản của tôi";
$openTab = session('open_tab', 'overview');
if ($errors->has('name') || $errors->has('email')) {
    $openTab = 'account';
}
@endphp

@section('content')
<div class="profile-wrapper">
    <div class="profile-header">
        <div class="profile-avatar-section">
            <div class="profile-avatar">{{ mb_strtoupper(mb_substr($user['name'], 0, 1)) }}</div>
            <div class="profile-info">
                <h1 class="profile-name">{{ $user['name'] }}</h1>
                <p class="profile-email"><i class="fa-solid fa-envelope"></i> {{ $user['email'] }}</p>
                <p class="profile-join-date"><i class="fa-solid fa-calendar"></i> Tham gia ngày {{ date('d/m/Y', strtotime($user['created_at'])) }}</p>
            </div>
        </div>
    </div>

    <div class="profile-tabs" role="tablist">
        <button type="button" class="profile-tab {{ $openTab === 'overview' ? 'active' : '' }}" data-tab="overview"><i class="fa-solid fa-chart-simple"></i> Tổng quan</button>
        <button type="button" class="profile-tab {{ $openTab === 'account' ? 'active' : '' }}" data-tab="account"><i class="fa-solid fa-user-pen"></i> Thông tin</button>
        <button type="button" class="profile-tab {{ $openTab === 'security' ? 'active' : '' }}" data-tab="security"><i class="fa-solid fa-lock"></i> Bảo mật</button>
    </div>

    <div class="profile-content">
        <!-- Overview Panel -->
        <section class="tab-panel {{ $openTab === 'overview' ? 'active' : '' }}" data-panel="overview">
            <div class="stats-section">
                <h2 class="section-title"><i class="fa-solid fa-chart-simple"></i> Thống kê công việc</h2>
                <div class="stats-grid">
                    @foreach ([
                        'all' => ['total', 'Tổng công việc', 'total', 'fa-list-check'], 
                        'completed' => ['completed', 'Hoàn thành', 'completed', 'fa-circle-check'], 
                        'incomplete' => ['incomplete', 'Chưa hoàn thành', 'incomplete', 'fa-circle'], 
                        'overdue' => ['overdue', 'Quá hạn', 'overdue', 'fa-triangle-exclamation'], 
                        'my-day' => ['due_today', 'Đến hạn hôm nay', 'today', 'fa-sun'], 
                        'important' => ['important', 'Quan trọng', 'important', 'fa-star']
                    ] as $filter => [$key, $label, $color, $icon])
                        <a href="/tasks?filter={{ $filter }}" class="stat-card {{ $filter === 'overdue' && $stats['overdue'] > 0 ? 'is-alert' : '' }}">
                            <div class="stat-icon {{ $color }}"><i class="fa-solid {{ $icon }}"></i></div>
                            <div class="stat-content">
                                <h3 class="stat-value">{{ (int)$stats[$key] }}</h3>
                                <p class="stat-label">{{ $label }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>

                @if ($stats['total'] > 0)
                    <div class="progress-section">
                        <div class="progress-header">
                            <span class="progress-label">Tiến độ tổng thể</span>
                            <span class="progress-percentage">{{ (float)$stats['completion_rate'] }}%</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: {{ (float)$stats['completion_rate'] }}%"></div>
                        </div>
                        <div class="progress-info">
                            <span>Đã xong {{ (int)$stats['completed'] }} / {{ (int)$stats['total'] }} công việc</span>
                        </div>
                    </div>
                @else
                    <div class="empty-state-note">
                        <p>Bạn chưa có công việc nào. <a href="/tasks/create">Tạo công việc đầu tiên!</a></p>
                    </div>
                @endif
            </div>
        </section>

        <!-- Account Panel -->
        <section class="tab-panel {{ $openTab === 'account' ? 'active' : '' }}" data-panel="account">
            <div class="form-card">
                <h2 class="section-title"><i class="fa-solid fa-user-pen"></i> Cập nhật thông tin</h2>
                <form method="POST" action="/profile/update" novalidate>
                    @csrf
                    <div class="form-group">
                        <label for="profile_name">Họ và tên <span class="required">*</span></label>
                        <input type="text" id="profile_name" name="name" maxlength="100" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name', $user['name']) }}">
                        @error('name') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="profile_email">Email <span class="required">*</span></label>
                        <input type="email" id="profile_email" name="email" maxlength="255" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email', $user['email']) }}">
                        @error('email') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                    </div>
                    <div class="form-actions-right">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Security Panel -->
        <section class="tab-panel {{ $openTab === 'security' ? 'active' : '' }}" data-panel="security">
            <div class="form-card">
                <h2 class="section-title"><i class="fa-solid fa-lock"></i> Đổi mật khẩu</h2>
                <p class="form-intro">Nhập đúng mật khẩu hiện tại trước khi đặt mật khẩu mới.</p>
                <form method="POST" action="/profile/password" novalidate>
                    @csrf
                    <div class="form-group">
                        <label for="current_password">Mật khẩu hiện tại <span class="required">*</span></label>
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password" class="form-control {{ $errors->has('current_password') ? 'is-invalid' : '' }}">
                        @error('current_password') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="new_password">Mật khẩu mới <span class="required">*</span></label>
                        <input type="password" id="new_password" name="new_password" autocomplete="new-password" class="form-control {{ $errors->has('new_password') ? 'is-invalid' : '' }}">
                        @error('new_password') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                        <small class="form-hint">Tối thiểu 6 ký tự</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_new_password">Nhập lại mật khẩu mới <span class="required">*</span></label>
                        <input type="password" id="confirm_new_password" name="confirm_password" autocomplete="new-password" class="form-control {{ $errors->has('confirm_password') ? 'is-invalid' : '' }}">
                        @error('confirm_password') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                    </div>
                    <div class="form-actions-right">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Đổi mật khẩu</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection