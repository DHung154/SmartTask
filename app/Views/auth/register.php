<?php
use App\Core\Session;
use App\Core\Csrf;
use App\Core\View;

$old = $old ?? [];
$errors = $errors ?? [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? "\u{0110}\u{0103}ng k\u{00FD}") ?></title>
    <script>
        (function () { try { var saved = localStorage.getItem('theme'); var dark = window.matchMedia('(prefers-color-scheme: dark)').matches; if (saved === 'dark' || (!saved && dark)) document.documentElement.setAttribute('data-theme', 'dark'); } catch (e) {} })();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="auth-container"><div class="auth-box">
        <h1 class="auth-title"><i class="fa-solid fa-check-double"></i> To-Do MVC</h1>
        <h2 class="auth-subtitle">T&#7841;o t&#224;i kho&#7843;n m&#7899;i</h2>
        <?php if ($error = Session::getFlash('error')): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= View::e($error) ?></div><?php endif; ?>
        <form method="POST" action="/register" novalidate>
            <?= Csrf::field() ?>
            <div class="form-group"><label for="name">H&#7885; v&#224; t&#234;n</label><input type="text" id="name" name="name" maxlength="100" autofocus autocomplete="name" class="<?= View::invalid($errors, 'name') ?>" value="<?= View::old($old, 'name') ?>"><?= View::error($errors, 'name') ?></div>
            <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" maxlength="255" autocomplete="email" class="<?= View::invalid($errors, 'email') ?>" value="<?= View::old($old, 'email') ?>"><?= View::error($errors, 'email') ?></div>
            <div class="form-group"><label for="password">M&#7853;t kh&#7849;u</label><input type="password" id="password" name="password" autocomplete="new-password" class="<?= View::invalid($errors, 'password') ?>"><?= View::error($errors, 'password') ?><small>T&#7889;i thi&#7875;u 6 k&#253; t&#7921;</small></div>
            <div class="form-group"><label for="confirm_password">Nh&#7853;p l&#7841;i m&#7853;t kh&#7849;u</label><input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" class="<?= View::invalid($errors, 'confirm_password') ?>"><?= View::error($errors, 'confirm_password') ?></div>
            <button type="submit" class="btn btn-primary btn-block">&#272;&#259;ng k&#253;</button>
        </form>
        <p class="auth-link">&#272;&#227; c&#243; t&#224;i kho&#7843;n? <a href="/login">&#272;&#259;ng nh&#7853;p</a></p>
    </div></div>
</body>
</html>
