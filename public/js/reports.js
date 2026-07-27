// Biểu đồ trang Báo cáo. Màu lấy từ biến CSS nên tự khớp sáng/tối.
document.addEventListener('DOMContentLoaded', function () {
    var dataEl = document.getElementById('report-chart-data');
    if (!dataEl || typeof Chart === 'undefined') return;

    var data;
    try { data = JSON.parse(dataEl.textContent || '{}'); } catch (e) { return; }

    function cssVar(name, fallback) {
        var value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return value || fallback;
    }

    var textColor = cssVar('--text-muted', '#605e5c');
    var gridColor = cssVar('--border-color', '#edebe9');

    var palette = {
        high: '#d93025',
        normal: '#7b68ee',
        low: '#00bcf2',
        todo: '#a19f9d',
        doing: '#f5b041',
        review: '#00bcf2',
        done: '#107c10',
    };

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";

    function hasValues(values) {
        return (values || []).some(function (value) { return value > 0; });
    }

    var priorityCanvas = document.getElementById('priorityChart');
    if (priorityCanvas && data.priority && hasValues(data.priority.values)) {
        new Chart(priorityCanvas, {
            type: 'pie',
            data: {
                labels: data.priority.labels,
                datasets: [{
                    data: data.priority.values,
                    backgroundColor: [palette.high, palette.normal, palette.low],
                    borderColor: cssVar('--surface', '#ffffff'),
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
            },
        });
    }

    var statusCanvas = document.getElementById('statusChart');
    if (statusCanvas && data.status && hasValues(data.status.values)) {
        new Chart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: data.status.labels,
                datasets: [{
                    data: data.status.values,
                    backgroundColor: [palette.todo, palette.doing, palette.review, palette.done],
                    borderColor: cssVar('--surface', '#ffffff'),
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: { legend: { position: 'bottom' } },
            },
        });
    }

    var monthlyCanvas = document.getElementById('monthlyChart');
    if (monthlyCanvas && data.monthly && data.monthly.labels.length) {
        new Chart(monthlyCanvas, {
            type: 'bar',
            data: {
                labels: data.monthly.labels,
                datasets: [
                    {
                        label: 'Tổng',
                        data: data.monthly.total,
                        backgroundColor: palette.normal,
                        borderRadius: 4,
                    },
                    {
                        label: 'Hoàn thành',
                        data: data.monthly.completed,
                        backgroundColor: palette.done,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: textColor } },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: textColor },
                        grid: { color: gridColor },
                    },
                },
            },
        });
    }
});
