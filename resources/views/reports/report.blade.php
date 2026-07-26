@extends('layouts.app')

@php
$title = $title ?? "B\u{00E1}o c\u{00E1}o";
$stats = $stats ?? [];
$priorityCounts = array_merge(['low' => 0, 'normal' => 0, 'high' => 0], $priorityCounts ?? []);
@endphp

@section('content')
<header class="content-header"><div class="header-left"><h1><i class="fa-solid fa-chart-simple"></i> Báo cáo</h1><p class="current-date">Tổng quan tiến độ và mức ưu tiên</p></div><div class="header-right report-actions"><a href="/report/export.csv" class="btn btn-secondary"><i class="fa-solid fa-file-excel"></i> Excel/CSV</a><button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-file-pdf"></i> In PDF</button></div></header>
<div class="report-grid">
    <div class="report-card"><span class="report-label">Tổng công việc</span><strong>{{ (int)($stats['total'] ?? 0) }}</strong></div>
    <div class="report-card success"><span class="report-label">Hoàn thành</span><strong>{{ (int)($stats['completed'] ?? 0) }}</strong></div>
    <div class="report-card danger"><span class="report-label">Quá hạn</span><strong>{{ (int)($stats['overdue'] ?? 0) }}</strong></div>
    <div class="report-card info"><span class="report-label">Tỷ lệ hoàn thành</span><strong>{{ (float)($stats['completion_rate'] ?? 0) }}%</strong></div>
</div>
<section class="panel-block"><h2>Mức ưu tiên</h2><div class="priority-bars">@foreach (['high' => 'Cao', 'normal' => 'Bình thường', 'low' => 'Thấp'] as $key => $label)@php $value = (int)$priorityCounts[$key]; @endphp<div class="priority-row"><span class="priority-badge priority-{{ $key }}">{{ $label }}</span><div class="bar-track"><div class="bar-fill priority-{{ $key }}" style="width: {{ min(100, $value * 12) }}%"></div></div><strong>{{ $value }}</strong></div>@endforeach</div></section>
<section class="panel-block"><h2>6 tháng gần đây</h2><div class="monthly-list">@if (empty($monthlySummary))<p class="empty-hint">Chưa có dữ liệu để thống kê.</p>@else @foreach ($monthlySummary as $row)<div class="monthly-row"><span>{{ $row['month'] }}</span><span>{{ (int)$row['completed'] }} / {{ (int)$row['total'] }} hoàn thành</span></div>@endforeach @endif</div></section>
@endsection
