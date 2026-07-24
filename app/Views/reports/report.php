<?php
use App\Core\View;
$title = $title ?? "B\u{00E1}o c\u{00E1}o";
$stats = $stats ?? [];
$priorityCounts = array_merge(['low' => 0, 'normal' => 0, 'high' => 0], $priorityCounts ?? []);
ob_start();
?>
<header class="content-header"><div class="header-left"><h1><i class="fa-solid fa-chart-simple"></i> B&#225;o c&#225;o</h1><p class="current-date">T&#7893;ng quan ti&#7871;n &#273;&#7897; v&#224; m&#7913;c &#432;u ti&#234;n</p></div><div class="header-right report-actions"><a href="/report/export.csv" class="btn btn-secondary"><i class="fa-solid fa-file-excel"></i> Excel/CSV</a><button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-file-pdf"></i> In PDF</button></div></header>
<div class="report-grid">
    <div class="report-card"><span class="report-label">T&#7893;ng c&#244;ng vi&#7879;c</span><strong><?= (int)($stats['total'] ?? 0) ?></strong></div>
    <div class="report-card success"><span class="report-label">Ho&#224;n th&#224;nh</span><strong><?= (int)($stats['completed'] ?? 0) ?></strong></div>
    <div class="report-card danger"><span class="report-label">Qu&#225; h&#7841;n</span><strong><?= (int)($stats['overdue'] ?? 0) ?></strong></div>
    <div class="report-card info"><span class="report-label">T&#7927; l&#7879; ho&#224;n th&#224;nh</span><strong><?= (float)($stats['completion_rate'] ?? 0) ?>%</strong></div>
</div>
<section class="panel-block"><h2>M&#7913;c &#432;u ti&#234;n</h2><div class="priority-bars"><?php foreach (['high' => 'Cao', 'normal' => 'B&#236;nh th&#432;&#7901;ng', 'low' => 'Th&#7845;p'] as $key => $label): $value = (int)$priorityCounts[$key]; ?><div class="priority-row"><span class="priority-badge priority-<?= View::e($key) ?>"><?= $label ?></span><div class="bar-track"><div class="bar-fill priority-<?= View::e($key) ?>" style="width: <?= min(100, $value * 12) ?>%"></div></div><strong><?= $value ?></strong></div><?php endforeach; ?></div></section>
<section class="panel-block"><h2>6 th&#225;ng g&#7847;n &#273;&#226;y</h2><div class="monthly-list"><?php if (empty($monthlySummary)): ?><p class="empty-hint">Ch&#432;a c&#243; d&#7919; li&#7879;u &#273;&#7875; th&#7889;ng k&#234;.</p><?php else: foreach ($monthlySummary as $row): ?><div class="monthly-row"><span><?= View::e($row['month']) ?></span><span><?= (int)$row['completed'] ?> / <?= (int)$row['total'] ?> ho&#224;n th&#224;nh</span></div><?php endforeach; endif; ?></div></section>
<?php $content = ob_get_clean(); include __DIR__ . '/../layout.php'; ?>
