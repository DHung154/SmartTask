<?php
use App\Core\View;

$title = $title ?? "L\u{1ECB}ch deadline";
$month = $month ?? date('Y-m');
$tasksByDay = [];
foreach ($tasks ?? [] as $task) { $tasksByDay[$task['due_date']][] = $task; }
$busyDays = $tasksByDay;
uksort($busyDays, fn($a, $b) => strcmp($a, $b));
$maxTasksInDay = 1;
foreach ($busyDays as $items) { $maxTasksInDay = max($maxTasksInDay, count($items)); }
$firstDay = strtotime($month . '-01');
$daysInMonth = (int) date('t', $firstDay);
$startWeekday = (int) date('N', $firstDay);
$prevMonth = date('Y-m', strtotime('-1 month', $firstDay));
$nextMonth = date('Y-m', strtotime('+1 month', $firstDay));
$selectedDate = $selectedDate ?? '';
$selectedTasks = $selectedTasks ?? [];
ob_start();
?>
<header class="content-header">
    <div class="header-left">
        <h1><i class="fa-regular fa-calendar"></i> L&#7883;ch deadline</h1>
        <p class="current-date">Theo d&#245;i c&#244;ng vi&#7879;c &#273;&#7871;n h&#7841;n trong th&#225;ng <?= View::e(date('m/Y', $firstDay)) ?></p>
    </div>
    <div class="header-right">
        <a href="/calendar?month=<?= View::e($prevMonth) ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-chevron-left"></i></a>
        <a href="/calendar?month=<?= View::e(date('Y-m')) ?>" class="btn btn-secondary btn-sm">Th&#225;ng n&#224;y</a>
        <a href="/calendar?month=<?= View::e($nextMonth) ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-chevron-right"></i></a>
    </div>
</header>

<section class="calendar-overview">
    <div>
        <h2>S&#417; &#273;&#7891; vi&#7879;c trong th&#225;ng</h2>
        <p>Ng&#224;y n&#224;o nhi&#7873;u nhi&#7879;m v&#7909; s&#7869; c&#243; c&#7897;t cao h&#417;n. Trong &#244; ng&#224;y s&#7869; hi&#7875;n t&#7915;ng nhi&#7879;m v&#7909;, k&#7875; c&#7843; khi c&#243; 2-3 vi&#7879;c.</p>
    </div>
    <div class="workload-list">
        <?php if (empty($busyDays)): ?>
            <span class="empty-hint workload-empty">Th&#225;ng n&#224;y ch&#432;a c&#243; deadline.</span>
        <?php else: foreach ($busyDays as $date => $items): $count = count($items); ?>
            <a href="/calendar?month=<?= View::e($month) ?>&amp;day=<?= View::e($date) ?>#day-<?= View::e($date) ?>" class="workload-item">
                <span class="workload-item-date"><?= View::e(date('d/m', strtotime($date))) ?></span>
                <span class="workload-item-titles"><?= View::e(implode(', ', array_column($items, 'title'))) ?></span>
                <span class="workload-item-count"><?= $count ?> vi&#7879;c</span>
            </a>
        <?php endforeach; endif; ?>
    </div>
</section>

<div class="calendar-grid">
    <?php foreach (['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'] as $label): ?><div class="calendar-weekday"><?= View::e($label) ?></div><?php endforeach; ?>
    <?php for ($i = 1; $i < $startWeekday; $i++): ?><div class="calendar-day muted"></div><?php endfor; ?>
    <?php for ($day = 1; $day <= $daysInMonth; $day++): $date = $month . '-' . str_pad((string)$day, 2, '0', STR_PAD_LEFT); $dayTasks = $tasksByDay[$date] ?? []; ?>
        <div id="day-<?= View::e($date) ?>" class="calendar-day <?= $date === date('Y-m-d') ? 'today' : '' ?> <?= $dayTasks ? 'has-tasks' : '' ?> <?= $date === $selectedDate ? 'selected' : '' ?>">
            <div class="calendar-day-head"><a href="/calendar?month=<?= View::e($month) ?>&amp;day=<?= View::e($date) ?>#day-<?= View::e($date) ?>" class="calendar-date"><?= $day ?></a><?php if ($dayTasks): ?><span class="calendar-count"><?= count($dayTasks) ?> nhi&#7879;m v&#7909;</span><?php endif; ?></div>
            <div class="calendar-tasks">
                <?php foreach (array_slice($dayTasks, 0, 4) as $task): ?><a href="/tasks/edit?id=<?= (int)$task['id'] ?>" class="calendar-task priority-<?= View::e($task['priority'] ?? 'normal') ?>"><span class="task-dot"></span><span><?= View::e($task['title']) ?></span></a><?php endforeach; ?>
                <?php if (count($dayTasks) > 4): ?><span class="calendar-more">+<?= count($dayTasks) - 4 ?> vi&#7879;c n&#7919;a</span><?php endif; ?>
            </div>
        </div>
    <?php endfor; ?>
</div>
<?php if ($selectedDate !== ''): ?>
    <section class="calendar-detail" id="selected-day">
        <div><h2>Vi&#7879;c ng&#224;y <?= View::e(date('d/m/Y', strtotime($selectedDate))) ?></h2><p><?= count($selectedTasks) ?> nhi&#7879;m v&#7909; c&#243; deadline trong ng&#224;y n&#224;y.</p></div>
        <div class="calendar-detail-list">
            <?php if (empty($selectedTasks)): ?><p class="empty-hint">Kh&#244;ng c&#243; c&#244;ng vi&#7879;c trong ng&#224;y n&#224;y.</p>
            <?php else: foreach ($selectedTasks as $task): ?><a href="/tasks/edit?id=<?= (int)$task['id'] ?>" class="calendar-detail-task priority-<?= View::e($task['priority'] ?? 'normal') ?>"><span><?= View::e($task['title']) ?></span><small><?= !empty($task['completed']) ? '&#272;&#227; ho&#224;n th&#224;nh' : 'Ch&#432;a ho&#224;n th&#224;nh' ?></small></a><?php endforeach; endif; ?>
        </div>
    </section>
<?php endif; ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../layout.php'; ?>
