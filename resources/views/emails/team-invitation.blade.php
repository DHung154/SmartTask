@php
    $roleLabel = ($role ?? 'member') === 'admin' ? 'Quản trị' : 'Thành viên';
@endphp
<h2>Lời mời tham gia nhóm trên SmartTask</h2>

<p>Chào {{ $invitee->name ?? 'bạn' }},</p>

<p>
    <strong>{{ $inviter->name ?? 'Một thành viên' }}</strong> đã mời bạn tham gia nhóm
    <strong>{{ $team->name ?? 'SmartTask' }}</strong> với vai trò <strong>{{ $roleLabel }}</strong>.
</p>

@if (!empty($team->description))
    <p style="color: #605e5c;">{{ $team->description }}</p>
@endif

<p>
    Đăng nhập SmartTask rồi mở <strong>chuông thông báo</strong> ở góc trên bên phải để
    <strong>chấp nhận</strong> hoặc <strong>từ chối</strong> lời mời này.
</p>

<p>
    <a href="{{ rtrim(config('app.url'), '/') }}/teams"
       style="display: inline-block; background: #7b68ee; color: #ffffff; padding: 10px 18px; border-radius: 6px; text-decoration: none;">
        Mở SmartTask
    </a>
</p>

<p style="color: #605e5c; font-size: 13px;">
    Bạn chỉ trở thành thành viên của nhóm sau khi bấm chấp nhận. Nếu không muốn tham gia, bạn có thể bỏ qua email này.
</p>
