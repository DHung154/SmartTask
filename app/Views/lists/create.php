<?php
use App\Core\Csrf;
use App\Core\View;
$title = $title ?? "Danh s\u{00E1}ch m\u{1EDB}i";
$old = $old ?? [];
$errors = $errors ?? [];
ob_start();
?>
<div class="form-wrapper">
    <div class="content-header"><div class="header-title"><h1><i class="fa-solid fa-folder-plus"></i> T&#7841;o danh s&#225;ch m&#7899;i</h1></div><a href="/" class="btn-text"><i class="fa-solid fa-arrow-left"></i> Quay l&#7841;i</a></div>
    <div class="form-card"><form method="POST" action="/lists/create" novalidate>
        <?= Csrf::field() ?>
        <div class="form-group"><label for="name">T&#234;n danh s&#225;ch <span class="required">*</span></label><input type="text" id="name" name="name" maxlength="100" autofocus class="form-control <?= View::invalid($errors, 'name') ?>" placeholder="VD: &#272;i ch&#7907;, C&#244;ng vi&#7879;c, H&#7885;c t&#7853;p..." value="<?= View::old($old, 'name') ?>"><?= View::error($errors, 'name') ?><small class="form-hint">Nh&#243;m c&#225;c c&#244;ng vi&#7879;c li&#234;n quan v&#224;o c&#249;ng m&#7897;t danh s&#225;ch.</small></div>
        <div class="form-actions-right"><a href="/" class="btn btn-secondary">H&#7911;y</a><button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> T&#7841;o danh s&#225;ch</button></div>
    </form></div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layout.php'; ?>
