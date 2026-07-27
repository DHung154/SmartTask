<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Quên mật khẩu' }}</title>
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
        <h1 class="auth-title"><i class="fa-solid fa-key"></i> Quên mật khẩu</h1>
        <h2 class="auth-subtitle">Nhập email đã đăng ký, chúng tôi sẽ gửi link đặt lại mật khẩu.</h2>

        @if (session('error'))<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>@endif

        <form method="POST" action="/forgot-password" novalidate>
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autofocus autocomplete="email"
                       class="{{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}">
                @error('email') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
            </div>
            <button type="submit" class="btn btn-primary btn-block">Gửi link đặt lại</button>
        </form>

        <p class="auth-link"><a href="/login">Quay lại đăng nhập</a></p>
    </div></div>
</body>
</html>
