<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? "\u{0110}\u{0103}ng k\u{00FD}" }}</title>
    <script>
        (function () { try { var saved = localStorage.getItem('theme'); var dark = window.matchMedia('(prefers-color-scheme: dark)').matches; if (saved === 'dark' || (!saved && dark)) document.documentElement.setAttribute('data-theme', 'dark'); } catch (e) {} })();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="auth-container"><div class="auth-box">
        <h1 class="auth-title"><i class="fa-solid fa-check-double"></i> To-Do MVC</h1>
        <h2 class="auth-subtitle">Tạo tài khoản mới</h2>
        @if (session('error'))<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>@endif
        <form method="POST" action="/register" novalidate>
            @csrf
            <div class="form-group"><label for="name">Họ và tên</label><input type="text" id="name" name="name" maxlength="100" autofocus autocomplete="name" class="{{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name') }}">@error('name') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror</div>
            <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" maxlength="255" autocomplete="email" class="{{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}">@error('email') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror</div>
            <div class="form-group"><label for="password">Mật khẩu</label><input type="password" id="password" name="password" autocomplete="new-password" class="{{ $errors->has('password') ? 'is-invalid' : '' }}">@error('password') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror<small>Tối thiểu 6 ký tự</small></div>
            <div class="form-group"><label for="confirm_password">Nhập lại mật khẩu</label><input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" class="{{ $errors->has('confirm_password') ? 'is-invalid' : '' }}">@error('confirm_password') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror</div>
            <button type="submit" class="btn btn-primary btn-block">Đăng ký</button>
        </form>
        <p class="auth-link">Đã có tài khoản? <a href="/login">Đăng nhập</a></p>
    </div></div>
</body>
</html>
