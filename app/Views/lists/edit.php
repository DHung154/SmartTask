<?php
use App\Core\Csrf;
use App\Core\View;
$title = $title ?? "S\u{1EED}a danh s\u{00E1}ch";
$old = $old ?? [];
$errors = $errors ?? [];
$currentName = $old['name'] ?? $list['name'];
ob_start();
?>
<div class="form-wrapper">
    <div class="content-header"><div class="header-title"><h1><i class="fa-solid fa-pen-to-square"></i> S&#7917;a danh s&#225;ch</h1></div><a href="/tasks?list=<?= (int)$list['id'] ?>" class="btn-text"><i class="fa-solid fa-arrow-left"></i> Quay l&#7841;i</a></div>
    <div class="form-card"><form method="POST" action="/lists/edit?id=<?= (int)$list['id'] ?>" novalidate>
        <?= Csrf::field() ?>
        <div class="form-group"><label for="name">T&#234;n danh s&#225;ch <span class="required">*</span></label><input type="text" id="name" name="name" maxlength="100" autofocus class="form-control <?= View::invalid($errors, 'name') ?>" value="<?= View::e($currentName) ?>"><?= View::error($errors, 'name') ?></div>
        <div class="form-actions-right"><a href="/tasks?list=<?= (int)$list['id'] ?>" class="btn btn-secondary">H&#7911;y</a><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> L&#432;u thay &#273;&#7893;i</button></div>
    </form></div>
    <div class="form-danger-zone"><form method="POST" action="/lists/delete" class="inline-form" onsubmit="return confirm('Xoa danh sach nay? Cac cong viec ben trong se duoc chuyen ve muc Cong viec.');">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$list['id'] ?>">
        <button type="submit" class="btn-text text-danger"><i class="fa-solid fa-trash"></i> X&#243;a danh s&#225;ch n&#224;y</button>
    </form></div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layout.php'; ?>
