@extends('layouts.app')

@php
$title = $title ?? 'Báo cáo';
$stats = $stats ?? [];
$priorityCounts = array_merge(['low' => 0, 'normal' => 0, 'high' => 0], $priorityCounts ?? []);
$statusCounts = $statusCounts ?? array_fill_keys(App\Models\Task::STATUSES, 0);
$statusLabels = App\Models\Task::statusLabels();

// Biểu đồ theo tháng đọc từ cũ → mới cho dễ nhìn xu hướng.
$monthly = array_reverse($monthlySummary ?? []);

$chartData = [
    'priority' => [
        'labels' => ['Cao', 'Bình thường', 'Thấp'],
        'values' => [(int) $priorityCounts['high'], (int) $priorityCounts['normal'], (int) $priorityCounts['low']],
    ],
    'status' => [
        'labels' => array_values($statusLabels),
        'values' => array_map(fn($key) => (int) ($statusCounts[$key] ?? 0), array_keys($statusLabels)),
    ],
    'monthly' => [
        'labels'    => array_map(fn($row) => $row['month'], $monthly),
        'total'     => array_map(fn($row) => (int) $row['total'], $monthly),
        'completed' => array_map(fn($row) => (int) $row['completed'], $monthly),
    ],
];
@endphp

@section('content')
<link rel="stylesheet" href="/css/charts.css?v=20260728a">

<header class="content-header">
    <div class="header-left">
        <h1><i class="fa-solid fa-chart-simple"></i> Báo cáo</h1>
        <p class="current-date">Tổng quan tiến độ và mức ưu tiên</p>
    </div>
    <div class="header-right report-actions">
        <a href="/report/export.csv" class="btn btn-secondary"><i class="fa-solid fa-file-excel"></i> Excel/CSV</a>
        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-file-pdf"></i> In PDF</button>
    </div>
</header>

<div class="report-grid">
    <div class="report-card"><span class="report-label">Tổng công việc</span><strong>{{ (int)($stats['total'] ?? 0) }}</strong></div>
    <div class="report-card success"><span class="report-label">Hoàn thành</span><strong>{{ (int)($stats['completed'] ?? 0) }}</strong></div>
    <div class="report-card danger"><span class="report-label">Quá hạn</span><strong>{{ (int)($stats['overdue'] ?? 0) }}</strong></div>
    <div class="report-card info"><span class="report-label">Tỷ lệ hoàn thành</span><strong>{{ (float)($stats['completion_rate'] ?? 0) }}%</strong></div>
</div>

@if ((int)($stats['total'] ?? 0) === 0)
    <section class="panel-block">
        <p class="empty-hint">Chưa có công việc nào để thống kê. Thêm vài công việc rồi quay lại đây.</p>
    </section>
@else
    <div class="chart-grid">
        <section class="panel-block chart-block">
            <h2>Theo mức ưu tiên</h2>
            <div class="chart-holder"><canvas id="priorityChart" aria-label="Biểu đồ tròn theo mức ưu tiên" role="img"></canvas></div>
        </section>

        <section class="panel-block chart-block">
            <h2>Theo trạng thái</h2>
            <div class="chart-holder"><canvas id="statusChart" aria-label="Biểu đồ vành khuyên theo trạng thái" role="img"></canvas></div>
        </section>
    </div>

    <section class="panel-block chart-block">
        <h2>Công việc theo tháng</h2>
        @if (empty($monthly))
            <p class="empty-hint">Chưa có dữ liệu để thống kê.</p>
        @else
            <div class="chart-holder chart-holder-wide"><canvas id="monthlyChart" aria-label="Biểu đồ cột theo tháng" role="img"></canvas></div>
        @endif
    </section>
@endif

<section class="panel-block">
    <h2>Chi tiết theo tháng</h2>
    <div class="monthly-list">
        @if (empty($monthlySummary))
            <p class="empty-hint">Chưa có dữ liệu để thống kê.</p>
        @else
            @foreach ($monthlySummary as $row)
                <div class="monthly-row">
                    <span>{{ $row['month'] }}</span>
                    <span>{{ (int)$row['completed'] }} / {{ (int)$row['total'] }} hoàn thành</span>
                </div>
            @endforeach
        @endif
    </div>
</section>

<script type="application/json" id="report-chart-data">@json($chartData)</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/js/reports.js?v=20260728a"></script>
@endsection
