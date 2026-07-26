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

## Gmail SMTP

Điền thêm vào `.env`:

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your-16-character-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your@gmail.com
```

`MAIL_PASSWORD` phải là **App Password** của Google, không phải mật khẩu Gmail thường. Gửi lời mời thành viên chạy ngay khi thêm thành viên; nhắc deadline chạy bằng `php artisan tasks:send-reminders`.
