<?php
use App\Core\View;
$title = $title ?? "Nh\u{1EAD}t k\u{00FD}";
ob_start();
?>
<header class="content-header"><div class="header-left"><h1><i class="fa-solid fa-clock-rotate-left"></i> Nhật ký</h1><p class="current-date">C&#225;c thao t&#225;c g&#7847;n &#273;&#226;y tr&#234;n c&#244;ng vi&#7879;c</p></div></header>
<div class="activity-list">
    <?php if (empty($logs)): ?><div class="empty-state-vertical"><div class="empty-icon"><i class="fa-solid fa-clock-rotate-left"></i></div><p class="empty-title">Ch&#432;a c&#243; ho&#7841;t &#273;&#7897;ng n&#224;o.</p></div>
    <?php else: foreach ($logs as $log): ?><div class="activity-item"><div class="activity-icon"><i class="fa-solid fa-bolt"></i></div><div><strong><?= View::e($log['message']) ?></strong><p><?= View::e(date('d/m/Y H:i', strtotime($log['created_at']))) ?></p></div></div><?php endforeach; endif; ?>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layout.php'; ?>
