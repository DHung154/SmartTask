<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? "\u{0110}\u{0103}ng nh\u{1EAD}p" }}</title>
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
        <h2 class="auth-subtitle">Đăng nhập vào tài khoản</h2>
        @if (session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>@endif
        <form method="POST" action="/login" novalidate>
            @csrf
            <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" autofocus autocomplete="email" class="{{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}">@error('email') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror</div>
            <div class="form-group"><label for="password">Mật khẩu</label><input type="password" id="password" name="password" autocomplete="current-password" class="{{ $errors->has('password') ? 'is-invalid' : '' }}">@error('password') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror</div>
            <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
        </form>
        <p class="auth-link">Chưa có tài khoản? <a href="/register">Đăng ký ngay</a></p>
    </div></div>
</body>
</html>
