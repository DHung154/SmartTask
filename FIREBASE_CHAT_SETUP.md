# Firebase group chat setup

Chat nhóm dùng Firebase Realtime Database và Storage qua Web SDK CDN nên không cần cài thêm npm package.

1. Tạo Firebase project và đăng ký một Web App.
2. Bật **Authentication → Sign-in method → Anonymous**.
3. Tạo **Realtime Database** và Storage.
4. Chép cấu hình Web App vào `.env`:

```env
FIREBASE_API_KEY=...
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_PROJECT_ID=your-project
FIREBASE_STORAGE_BUCKET=your-project.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=...
FIREBASE_APP_ID=...
```

5. Dán nội dung `database.rules.json` vào tab **Realtime Database → Rules** và `storage.rules` vào **Storage → Rules**.
6. Chạy `php artisan optimize:clear`, sau đó mở lại `/teams/detail?id=...`.

Chat được lưu theo `teamChats/{teamId}/messages` trong Realtime Database; file nằm trong Storage và giới hạn 20 MB.

Bản này dùng Anonymous Auth để chạy nhanh cho đồ án. Khi đưa lên production, nên đổi sang Firebase custom token liên kết với tài khoản Laravel để Rules kiểm tra chính xác thành viên từng nhóm.

## Email

### Mặc định: Mailpit (chạy local, không cần tài khoản)

`.env` đang trỏ vào Mailpit có sẵn trong Laragon nên email chạy được ngay mà không cần App Password:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=no-reply@smarttask.local
```

Bật Mailpit rồi mở http://localhost:8025 để đọc email đã gửi:

```bash
D:/laragon/bin/mailpit/1.22.3/mailpit.exe --smtp 127.0.0.1:1025 --listen 127.0.0.1:8025
```

Trong Laragon có thể bật sẵn qua **Menu → Tools → Mailpit**.

### Gmail thật

Bỏ dấu `#` ở khối Gmail trong `.env` rồi điền:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your-16-character-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your@gmail.com
```

`MAIL_PASSWORD` phải là **App Password** của Google, không phải mật khẩu Gmail thường.

> **Lưu ý:** `MAIL_FROM_ADDRESS` bắt buộc phải có giá trị. Để trống thì mọi email đều lỗi
> `An email must have a "From" or a "Sender" header`.

Sau khi đổi `.env` nhớ chạy `php artisan optimize:clear`.

### Email của ứng dụng

- **Lời mời vào nhóm** — gửi ngay khi bấm "Gửi lời mời" ở trang chi tiết nhóm. Người nhận phải vào chuông thông báo bấm chấp nhận thì mới thành thành viên.
- **Nhắc deadline** — chạy bằng `php artisan tasks:send-reminders`, đã hẹn giờ 08:00 hằng ngày trong `app/Console/Kernel.php`.
