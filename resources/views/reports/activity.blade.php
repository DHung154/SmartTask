@extends('layouts.app')

@php
$title = $title ?? "Nh\u{1EAD}t k\u{00FD}";
@endphp

@section('content')
<header class="content-header"><div class="header-left"><h1><i class="fa-solid fa-clock-rotate-left"></i> Nhật ký</h1><p class="current-date">Các thao tác gần đây trên công việc</p></div></header>
<div class="activity-list">
    @if (empty($logs))<div class="empty-state-vertical"><div class="empty-icon"><i class="fa-solid fa-clock-rotate-left"></i></div><p class="empty-title">Chưa có hoạt động nào.</p></div>
    @else
        @foreach ($logs as $log)<div class="activity-item"><div class="activity-icon"><i class="fa-solid fa-bolt"></i></div><div><strong>{{ $log['message'] }}</strong><p>{{ date('d/m/Y H:i', strtotime($log['created_at'])) }}</p></div></div>@endforeach
    @endif
</div>
@endsection
