<?php
use App\Core\Session;
use App\Core\Csrf;
use App\Core\View;

$title = $title ?? "T\u{00E0}i kho\u{1EA3}n c\u{1EE7}a t\u{00F4}i";
$old = $old ?? [];
$errors = $errors ?? [];
$openTab = Session::getFlash('open_tab') ?: 'overview';
if (isset($errors['name']) || isset($errors['email'])) $openTab = 'account';
ob_start();
?>
<div class="profile-wrapper">
    <div class="profile-header"><div class="profile-avatar-section"><div class="profile-avatar"><?= View::e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></div><div class="profile-info"><h1 class="profile-name"><?= View::e($user['name']) ?></h1><p class="profile-email"><i class="fa-solid fa-envelope"></i> <?= View::e($user['email']) ?></p><p class="profile-join-date"><i class="fa-solid fa-calendar"></i> Tham gia ng&#224;y <?= View::e(date('d/m/Y', strtotime($user['created_at']))) ?></p></div></div></div>
    <div class="profile-tabs" role="tablist">
        <button type="button" class="profile-tab <?= $openTab === 'overview' ? 'active' : '' ?>" data-tab="overview"><i class="fa-solid fa-chart-simple"></i> T&#7893;ng quan</button>
        <button type="button" class="profile-tab <?= $openTab === 'account' ? 'active' : '' ?>" data-tab="account"><i class="fa-solid fa-user-pen"></i> Th&#244;ng tin</button>
        <button type="button" class="profile-tab <?= $openTab === 'security' ? 'active' : '' ?>" data-tab="security"><i class="fa-solid fa-lock"></i> B&#7843;o m&#7853;t</button>
    </div>
    <div class="profile-content">
        <section class="tab-panel <?= $openTab === 'overview' ? 'active' : '' ?>" data-panel="overview">
            <div class="stats-section"><h2 class="section-title"><i class="fa-solid fa-chart-simple"></i> Th&#7889;ng k&#234; c&#244;ng vi&#7879;c</h2><div class="stats-grid">
                <?php foreach (['all' => ['total', 'T&#7893;ng c&#244;ng vi&#7879;c', 'total', 'fa-list-check'], 'completed' => ['completed', 'Ho&#224;n th&#224;nh', 'completed', 'fa-circle-check'], 'incomplete' => ['incomplete', 'Ch&#432;a ho&#224;n th&#224;nh', 'incomplete', 'fa-circle'], 'overdue' => ['overdue', 'Qu&#225; h&#7841;n', 'overdue', 'fa-triangle-exclamation'], 'my-day' => ['due_today', '&#272;&#7871;n h&#7841;n h&#244;m nay', 'today', 'fa-sun'], 'important' => ['important', 'Quan tr&#7885;ng', 'important', 'fa-star']] as $filter => [$key, $label, $color, $icon]): ?>
                    <a href="/tasks?filter=<?= $filter ?>" class="stat-card <?= $filter === 'overdue' && $stats['overdue'] > 0 ? 'is-alert' : '' ?>"><div class="stat-icon <?= $color ?>"><i class="fa-solid <?= $icon ?>"></i></div><div class="stat-content"><h3 class="stat-value"><?= (int)$stats[$key] ?></h3><p class="stat-label"><?= $label ?></p></div></a>
                <?php endforeach; ?>
            </div>
            <?php if ($stats['total'] > 0): ?><div class="progress-section"><div class="progress-header"><span class="progress-label">Ti&#7871;n &#273;&#7897; t&#7893;ng th&#7875;</span><span class="progress-percentage"><?= (float)$stats['completion_rate'] ?>%</span></div><div class="progress-bar-container"><div class="progress-bar-fill" style="width: <?= (float)$stats['completion_rate'] ?>%"></div></div><div class="progress-info"><span>&#272;&#227; xong <?= (int)$stats['completed'] ?> / <?= (int)$stats['total'] ?> c&#244;ng vi&#7879;c</span></div></div><?php else: ?><div class="empty-state-note"><p>B&#7841;n ch&#432;a c&#243; c&#244;ng vi&#7879;c n&#224;o. <a href="/tasks/create">T&#7841;o c&#244;ng vi&#7879;c &#273;&#7847;u ti&#234;n!</a></p></div><?php endif; ?>
            </div>
        </section>
        <section class="tab-panel <?= $openTab === 'account' ? 'active' : '' ?>" data-panel="account"><div class="form-card"><h2 class="section-title"><i class="fa-solid fa-user-pen"></i> C&#7853;p nh&#7853;t th&#244;ng tin</h2><form method="POST" action="/profile/update" novalidate><?= Csrf::field() ?><div class="form-group"><label for="profile_name">H&#7885; v&#224; t&#234;n <span class="required">*</span></label><input type="text" id="profile_name" name="name" maxlength="100" class="form-control <?= View::invalid($errors, 'name') ?>" value="<?= View::e($old['name'] ?? $user['name']) ?>"><?= View::error($errors, 'name') ?></div><div class="form-group"><label for="profile_email">Email <span class="required">*</span></label><input type="email" id="profile_email" name="email" maxlength="255" class="form-control <?= View::invalid($errors, 'email') ?>" value="<?= View::e($old['email'] ?? $user['email']) ?>"><?= View::error($errors, 'email') ?></div><div class="form-actions-right"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> L&#432;u thay &#273;&#7893;i</button></div></form></div></section>
        <section class="tab-panel <?= $openTab === 'security' ? 'active' : '' ?>" data-panel="security"><div class="form-card"><h2 class="section-title"><i class="fa-solid fa-lock"></i> &#272;&#7893;i m&#7853;t kh&#7849;u</h2><p class="form-intro">Nh&#7853;p &#273;&#250;ng m&#7853;t kh&#7849;u hi&#7879;n t&#7841;i tr&#432;&#7899;c khi &#273;&#7863;t m&#7853;t kh&#7849;u m&#7899;i.</p><form method="POST" action="/profile/password" novalidate><?= Csrf::field() ?><div class="form-group"><label for="current_password">M&#7853;t kh&#7849;u hi&#7879;n t&#7841;i <span class="required">*</span></label><input type="password" id="current_password" name="current_password" autocomplete="current-password" class="form-control <?= View::invalid($errors, 'current_password') ?>"><?= View::error($errors, 'current_password') ?></div><div class="form-group"><label for="new_password">M&#7853;t kh&#7849;u m&#7899;i <span class="required">*</span></label><input type="password" id="new_password" name="new_password" autocomplete="new-password" class="form-control <?= View::invalid($errors, 'new_password') ?>"><?= View::error($errors, 'new_password') ?><small class="form-hint">T&#7889;i thi&#7875;u 6 k&#253; t&#7921;</small></div><div class="form-group"><label for="confirm_new_password">Nh&#7853;p l&#7841;i m&#7853;t kh&#7849;u m&#7899;i <span class="required">*</span></label><input type="password" id="confirm_new_password" name="confirm_password" autocomplete="new-password" class="form-control <?= View::invalid($errors, 'confirm_password') ?>"><?= View::error($errors, 'confirm_password') ?></div><div class="form-actions-right"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> &#272;&#7893;i m&#7853;t kh&#7849;u</button></div></form></div></section>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layout.php'; ?>
