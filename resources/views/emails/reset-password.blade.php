<h2>Đặt lại mật khẩu SmartTask</h2>

<p>Chào {{ $user->name ?? 'bạn' }},</p>

<p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản này. Bấm nút bên dưới để đặt mật khẩu mới:</p>

<p>
    <a href="{{ $url }}"
       style="display: inline-block; background: #7b68ee; color: #ffffff; padding: 10px 18px; border-radius: 6px; text-decoration: none;">
        Đặt lại mật khẩu
    </a>
</p>

<p style="color: #605e5c; font-size: 13px;">
    Link này hết hạn sau {{ $minutes }} phút. Nếu bạn không yêu cầu đổi mật khẩu, hãy bỏ qua email này —
    mật khẩu hiện tại của bạn vẫn giữ nguyên.
</p>

<p style="color: #605e5c; font-size: 12px; word-break: break-all;">
    Nút không bấm được? Dán link này vào trình duyệt:<br>{{ $url }}
</p>
