<?php
use App\Core\Csrf;
use App\Core\View;
use App\Models\Task;

$title = $title ?? "C\u{00F4}ng vi\u{1EC7}c";

$filter     = $active_filter ?? 'inbox';
$sort       = $sort ?? 'smart';
$page       = $page ?? 1;
$totalPages = $totalPages ?? 1;
$totalTasks = $totalTasks ?? count($tasks ?? []);
$isTrash    = ($filter === 'trash');
$isSearch   = ($filter === 'search');
$search_query = $search_query ?? '';
$backUrl = View::currentUrl();

$buildUrl = function (array $overrides = []) use ($filter, $sort, $page, $isSearch, $search_query) {
    $params = ['sort' => $sort, 'page' => $page];

    if ($isSearch) {
        $base = '/tasks/search';
        $params['q'] = $search_query ?? '';
    } else {
        $base = '/tasks';
        if (is_numeric($filter)) {
            $params['list'] = $filter;
        } else {
            $params['filter'] = $filter;
        }
    }

    return $base . '?' . http_build_query(array_merge($params, $overrides));
};

ob_start();
?>

<header class="content-header">
    <div class="header-left">
        <div class="header-title-row">
            <h1><?= View::e($title) ?></h1>

            <?php if (!empty($currentList)): ?>
                <div class="list-actions">
                    <a href="/lists/edit?id=<?= (int)$currentList['id'] ?>" class="btn-icon" title="&#272;&#7893;i t&#234;n danh s&#225;ch">
                        <i class="fa-solid fa-pen"></i>
                    </a>

                    <form method="POST" action="/lists/delete" class="inline-form"
                          onsubmit="return confirm('Xoa danh sach nay? Cac cong viec ben trong se duoc chuyen ve muc Cong viec.');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int)$currentList['id'] ?>">
                        <button type="submit" class="btn-icon text-danger" title="X&#243;a danh s&#225;ch">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <p class="current-date"><?= View::e(date('d/m/Y')) ?> &middot; <?= (int)$totalTasks ?> c&#244;ng vi&#7879;c</p>
    </div>

    <div class="header-right">
        <?php if (!$isTrash && $totalTasks > 1): ?>
            <form method="GET" action="<?= $isSearch ? '/tasks/search' : '/tasks' ?>" class="sort-form">
                <?php if ($isSearch): ?>
                    <input type="hidden" name="q" value="<?= View::e($search_query ?? '') ?>">
                <?php elseif (is_numeric($filter)): ?>
                    <input type="hidden" name="list" value="<?= (int)$filter ?>">
                <?php else: ?>
                    <input type="hidden" name="filter" value="<?= View::e($filter) ?>">
                <?php endif; ?>

                <label for="sort" class="sort-label"><i class="fa-solid fa-arrow-down-wide-short"></i></label>
                <select name="sort" id="sort" class="sort-select" onchange="this.form.submit()">
                    <?php foreach (Task::sortLabels() as $key => $label): ?>
                        <option value="<?= View::e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>>
                            <?= View::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>

        <?php if ($isTrash && $totalTasks > 0): ?>
            <form method="POST" action="/tasks/empty-trash" class="inline-form"
                  onsubmit="return confirm('Xoa vinh vien toan bo cong viec trong thung rac? Khong the hoan tac.');">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-trash-can"></i> D&#7885;n s&#7841;ch th&#249;ng r&#225;c
                </button>
            </form>
        <?php endif; ?>
    </div>
</header>

<?php if (!$isTrash && !$isSearch && !empty($stats) && $stats['total'] > 0): ?>
    <div class="stats-strip">
        <a href="/tasks?filter=all" class="stat-chip">
            <span class="stat-chip-value"><?= (int)$stats['total'] ?></span>
            <span class="stat-chip-label">T&#7893;ng s&#7889;</span>
        </a>
        <a href="/tasks?filter=completed" class="stat-chip stat-done">
            <span class="stat-chip-value"><?= (int)$stats['completed'] ?></span>
            <span class="stat-chip-label">Ho&#224;n th&#224;nh</span>
        </a>
        <a href="/tasks?filter=incomplete" class="stat-chip stat-todo">
            <span class="stat-chip-value"><?= (int)$stats['incomplete'] ?></span>
            <span class="stat-chip-label">C&#242;n l&#7841;i</span>
        </a>
        <a href="/tasks?filter=overdue" class="stat-chip <?= $stats['overdue'] > 0 ? 'stat-overdue' : '' ?>">
            <span class="stat-chip-value"><?= (int)$stats['overdue'] ?></span>
            <span class="stat-chip-label">Qu&#225; h&#7841;n</span>
        </a>
        <a href="/tasks?filter=important" class="stat-chip stat-important">
            <span class="stat-chip-value"><?= (int)$stats['important'] ?></span>
            <span class="stat-chip-label">Quan tr&#7885;ng</span>
        </a>

        <div class="stat-progress" title="T&#7927; l&#7879; ho&#224;n th&#224;nh">
            <div class="stat-progress-bar">
                <div class="stat-progress-fill" style="width: <?= (float)$stats['completion_rate'] ?>%"></div>
            </div>
            <span class="stat-progress-text"><?= (float)$stats['completion_rate'] ?>%</span>
        </div>
    </div>
<?php endif; ?>

<div class="task-list-container">
    <?php if (!$isTrash && !$isSearch): ?>
        <form method="POST" action="/tasks/quick-add" class="quick-add-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="redirect" value="<?= $backUrl ?>">
            <input type="hidden" name="filter" value="<?= View::e($filter) ?>">

            <div class="quick-add-icon"><i class="fa-solid fa-plus"></i></div>
            <input type="text" name="title" class="quick-add-input" maxlength="200"
                   placeholder="Th&#234;m c&#244;ng vi&#7879;c - g&#245; r&#7891;i nh&#7845;n Enter" required>
            <button type="submit" class="quick-add-btn">Th&#234;m</button>
            <a href="/tasks/create<?= is_numeric($filter) ? '?list=' . (int)$filter : '' ?>"
               class="quick-add-more" title="Th&#234;m v&#7899;i &#273;&#7847;y &#273;&#7911; t&#249;y ch&#7885;n">
                <i class="fa-solid fa-sliders"></i>
            </a>
        </form>
    <?php endif; ?>

    <?php if (empty($tasks)): ?>
        <div class="empty-state-vertical">
            <div class="empty-icon">
                <i class="fa-solid <?= $isTrash ? 'fa-trash-can' : ($isSearch ? 'fa-magnifying-glass' : 'fa-clipboard-check') ?>"></i>
            </div>
            <p class="empty-title"><?= View::e($emptyText ?? "Ch\u{01B0}a c\u{00F3} c\u{00F4}ng vi\u{1EC7}c n\u{00E0}o \u{1EDF} \u{0111}\u{00E2}y.") ?></p>

            <?php if ($isSearch): ?>
                <a href="/" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> V&#7873; danh s&#225;ch
                </a>
            <?php elseif (!$isTrash): ?>
                <p class="empty-hint">D&#249;ng &#244; "Th&#234;m c&#244;ng vi&#7879;c" &#7903; tr&#234;n &#273;&#7875; b&#7855;t &#273;&#7847;u.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
            <?php
            $isCompleted = !empty($task['completed']);
            $isImportant = !empty($task['is_important']);
            $isOverdue = false;
            $dueLabel = '';

            if (!empty($task['due_date'])) {
                $dueTs = strtotime($task['due_date']);
                $todayTs = strtotime(date('Y-m-d'));
                $isOverdue = ($dueTs < $todayTs && !$isCompleted);

                if ($dueTs === $todayTs) {
                    $dueLabel = "H\u{00F4}m nay";
                } elseif ($dueTs === strtotime('+1 day', $todayTs)) {
                    $dueLabel = "Ng\u{00E0}y mai";
                } elseif ($dueTs === strtotime('-1 day', $todayTs)) {
                    $dueLabel = "H\u{00F4}m qua";
                } else {
                    $dueLabel = date('d/m/Y', $dueTs);
                }
            }
            ?>

            <div class="task-item <?= $isCompleted ? 'completed' : '' ?> <?= $isImportant ? 'is-important' : '' ?>">
                <?php if ($isTrash): ?>
                    <div class="task-checkbox-wrapper is-static">
                        <div class="custom-checkbox trashed"><i class="fa-solid fa-trash"></i></div>
                    </div>
                <?php else: ?>
                    <form method="POST" action="/tasks/toggle" class="inline-form">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
                        <input type="hidden" name="redirect" value="<?= $backUrl ?>">
                        <button type="submit" class="task-checkbox-wrapper"
                                title="<?= $isCompleted ? 'B&#7887; &#273;&#225;nh d&#7845;u ho&#224;n th&#224;nh' : '&#272;&#225;nh d&#7845;u ho&#224;n th&#224;nh' ?>">
                            <span class="custom-checkbox">
                                <?php if ($isCompleted): ?><i class="fa-solid fa-check"></i><?php endif; ?>
                            </span>
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($isTrash): ?>
                    <div class="task-content-link is-static">
                        <div class="task-content">
                            <span class="task-title"><?= View::e($task['title']) ?></span>
                            <div class="task-meta">
                                <span class="meta-deleted">
                                    <i class="fa-regular fa-clock"></i>
                                    &#272;&#227; x&#243;a <?= View::e(date('d/m/Y H:i', strtotime($task['deleted_at']))) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/tasks/edit?id=<?= (int)$task['id'] ?>" class="task-content-link">
                        <div class="task-content">
                            <span class="task-title"><?= View::e($task['title']) ?></span>

                            <div class="task-meta">
                                <?php if (!empty($task['description'])): ?>
                                    <span class="meta-note" title="C&#243; ghi ch&#250;">
                                        <i class="fa-regular fa-note-sticky"></i>
                                    </span>
                                <?php endif; ?>

                                <?php if (($task['priority'] ?? 'normal') !== 'normal'): ?>
                                    <span class="priority-badge priority-<?= View::e($task['priority']) ?>">
                                        <?= ($task['priority'] === 'high') ? '&#431;u ti&#234;n cao' : '&#431;u ti&#234;n th&#7845;p' ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($task['attachment_path'])): ?>
                                    <span class="attachment-link" title="<?= View::e($task['attachment_name'] ?? "File \u{0111}\u{00ED}nh k\u{00E8}m") ?>">
                                        <i class="fa-solid fa-paperclip"></i>
                                    </span>
                                <?php endif; ?>

                                <?php if ($dueLabel !== ''): ?>
                                    <span class="meta-date <?= $isOverdue ? 'text-danger' : '' ?>">
                                        <i class="fa-regular fa-calendar"></i> <?= View::e($dueLabel) ?>
                                        <?= $isOverdue ? '(qu&#225; h&#7841;n)' : '' ?>
                                    </span>
                                <?php endif; ?>
                                <span class="meta-progress"><i class="fa-solid fa-chart-line"></i> <?= (int)($task['progress'] ?? ($isCompleted ? 100 : 0)) ?>%</span>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>

                <div class="task-actions-group">
                    <?php if ($isTrash): ?>
                        <form method="POST" action="/tasks/restore" class="inline-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
                            <input type="hidden" name="redirect" value="<?= $backUrl ?>">
                            <button type="submit" class="action-btn restore-btn" title="Kh&#244;i ph&#7909;c">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                        </form>

                        <form method="POST" action="/tasks/force-delete" class="inline-form"
                              onsubmit="return confirm('Xoa vinh vien cong viec nay? Khong the hoan tac.');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
                            <input type="hidden" name="redirect" value="<?= $backUrl ?>">
                            <button type="submit" class="action-btn delete-btn" title="X&#243;a v&#297;nh vi&#7877;n">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/tasks/star" class="inline-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
                            <input type="hidden" name="redirect" value="<?= $backUrl ?>">
                            <button type="submit" class="action-btn star-btn <?= $isImportant ? 'active' : '' ?>"
                                    title="<?= $isImportant ? 'B&#7887; &#273;&#225;nh d&#7845;u quan tr&#7885;ng' : '&#272;&#225;nh d&#7845;u quan tr&#7885;ng' ?>">
                                <i class="<?= $isImportant ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                            </button>
                        </form>

                        <form method="POST" action="/tasks/delete" class="inline-form"
                              onsubmit="return confirm('Chuyen cong viec nay vao thung rac?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
                            <input type="hidden" name="redirect" value="<?= $backUrl ?>">
                            <button type="submit" class="action-btn delete-btn" title="Chuy&#7875;n v&#224;o th&#249;ng r&#225;c">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Ph&#226;n trang">
            <?php if ($page > 1): ?>
                <a href="<?= View::e($buildUrl(['page' => $page - 1])) ?>" class="page-btn">
                    <i class="fa-solid fa-chevron-left"></i> Tr&#432;&#7899;c
                </a>
            <?php else: ?>
                <span class="page-btn disabled"><i class="fa-solid fa-chevron-left"></i> Tr&#432;&#7899;c</span>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $start + 4);
            $start = max(1, $end - 4);
            ?>

            <?php if ($start > 1): ?>
                <a href="<?= View::e($buildUrl(['page' => 1])) ?>" class="page-num">1</a>
                <?php if ($start > 2): ?><span class="page-gap">&hellip;</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="page-num active"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= View::e($buildUrl(['page' => $i])) ?>" class="page-num"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span class="page-gap">&hellip;</span><?php endif; ?>
                <a href="<?= View::e($buildUrl(['page' => $totalPages])) ?>" class="page-num"><?= (int)$totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= View::e($buildUrl(['page' => $page + 1])) ?>" class="page-btn">
                    Sau <i class="fa-solid fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="page-btn disabled">Sau <i class="fa-solid fa-chevron-right"></i></span>
            <?php endif; ?>
        </nav>

        <p class="pagination-info">Trang <?= (int)$page ?> / <?= (int)$totalPages ?> - t&#7893;ng <?= (int)$totalTasks ?> c&#244;ng vi&#7879;c</p>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
