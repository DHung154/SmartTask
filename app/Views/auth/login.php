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
    <title><?= View::e($title ?? "\u{0110}\u{0103}ng nh\u{1EAD}p") ?></title>
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (saved === 'dark' || (!saved && prefersDark)) document.documentElement.setAttribute('data-theme', 'dark');
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="auth-container"><div class="auth-box">
        <h1 class="auth-title"><i class="fa-solid fa-check-double"></i> To-Do MVC</h1>
        <h2 class="auth-subtitle">&#272;&#259;ng nh&#7853;p v&#224;o t&#224;i kho&#7843;n</h2>
        <?php if ($success = Session::getFlash('success')): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= View::e($success) ?></div><?php endif; ?>
        <?php if ($error = Session::getFlash('error')): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= View::e($error) ?></div><?php endif; ?>
        <form method="POST" action="/login" novalidate>
            <?= Csrf::field() ?>
            <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" autofocus autocomplete="email" class="<?= View::invalid($errors, 'email') ?>" value="<?= View::old($old, 'email') ?>"><?= View::error($errors, 'email') ?></div>
            <div class="form-group"><label for="password">M&#7853;t kh&#7849;u</label><input type="password" id="password" name="password" autocomplete="current-password" class="<?= View::invalid($errors, 'password') ?>"><?= View::error($errors, 'password') ?></div>
            <button type="submit" class="btn btn-primary btn-block">&#272;&#259;ng nh&#7853;p</button>
        </form>
        <p class="auth-link">Ch&#432;a c&#243; t&#224;i kho&#7843;n? <a href="/register">&#272;&#259;ng k&#253; ngay</a></p>
    </div></div>
</body>
</html>
