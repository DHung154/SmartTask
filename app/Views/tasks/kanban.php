<?php
use App\Core\Csrf;
use App\Core\View;
$title = $title ?? 'Kanban';
$labels = ['todo' => ['C&#7847;n l&#224;m', 0, 'fa-list'], 'doing' => ['&#272;ang l&#224;m', 50, 'fa-spinner'], 'done' => ['Ho&#224;n th&#224;nh', 100, 'fa-circle-check']];
ob_start();
?>
<header class="content-header">
    <div class="header-left"><h1><i class="fa-solid fa-table-columns"></i> B&#7843;ng Kanban</h1><p class="current-date">Theo d&#245;i tr&#7841;ng th&#225;i v&#224; ti&#7871;n &#273;&#7897; c&#244;ng vi&#7879;c</p></div>
    <a href="/tasks/create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Th&#234;m vi&#7879;c</a>
</header>
<div class="kanban-board">
    <?php foreach ($labels as $key => [$label, $targetProgress, $icon]): ?>
        <section class="kanban-column kanban-<?= View::e($key) ?>">
            <header><h2><i class="fa-solid <?= View::e($icon) ?>"></i> <?= $label ?></h2><span><?= count($columns[$key]) ?></span></header>
            <div class="kanban-list">
                <?php if (empty($columns[$key])): ?><p class="kanban-empty">Ch&#432;a c&#243; c&#244;ng vi&#7879;c.</p><?php endif; ?>
                <?php foreach ($columns[$key] as $task): ?>
                    <article class="kanban-card">
                        <a href="/tasks/edit?id=<?= (int)$task['id'] ?>" class="kanban-title"><?= View::e($task['title']) ?></a>
                        <div class="kanban-meta"><span class="priority-badge priority-<?= View::e($task['priority'] ?? 'normal') ?>"><?= View::e(($task['priority'] ?? 'normal') === 'high' ? 'Cao' : (($task['priority'] ?? 'normal') === 'low' ? "Th\u{1EA5}p" : "B\u{00EC}nh th\u{01B0}\u{1EDD}ng")) ?></span><?php if (!empty($task['due_date'])): ?><span><i class="fa-regular fa-calendar"></i> <?= View::e(date('d/m/Y', strtotime($task['due_date']))) ?></span><?php endif; ?></div>
                        <div class="task-progress"><span style="width: <?= (int)($task['progress'] ?? 0) ?>%"></span></div>
                        <div class="kanban-actions">
                            <?php foreach ($labels as $moveKey => [$moveLabel, $moveProgress]): if ($moveKey === $key) continue; ?>
                                <form method="POST" action="/tasks/progress"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$task['id'] ?>"><input type="hidden" name="progress" value="<?= $moveProgress ?>"><input type="hidden" name="redirect" value="/kanban"><button type="submit" class="btn-icon" title="Chuy&#7875;n sang <?= $moveLabel ?>"><i class="fa-solid <?= $moveProgress < $targetProgress ? 'fa-arrow-left' : 'fa-arrow-right' ?>"></i></button></form>
                            <?php endforeach; ?>
                            <span><?= (int)($task['progress'] ?? 0) ?>%</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layout.php'; ?>
