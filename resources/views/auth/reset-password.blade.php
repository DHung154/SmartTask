<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Đặt lại mật khẩu' }}</title>
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
        <h1 class="auth-title"><i class="fa-solid fa-lock-open"></i> Đặt mật khẩu mới</h1>

        <form method="POST" action="/reset-password" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email"
                       class="{{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ $email }}">
                @error('email') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu mới</label>
                <input type="password" id="password" name="password" autofocus autocomplete="new-password"
                       class="{{ $errors->has('password') ? 'is-invalid' : '' }}">
                @error('password') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Nhập lại mật khẩu</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                       class="{{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}">
                @error('password_confirmation') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-block">Đổi mật khẩu</button>
        </form>

        <p class="auth-link"><a href="/login">Quay lại đăng nhập</a></p>
    </div></div>
</body>
</html>
