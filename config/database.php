<?php
/**
 * Cấu hình kết nối Database
 *
 * File này KHÔNG chứa mật khẩu thật. Mọi giá trị nhạy cảm đọc từ file .env
 * ở thư mục gốc (file .env đã được .gitignore, không đẩy lên GitHub).
 *
 * Cách dùng:
 *   1. Copy .env.example thành .env
 *   2. Sửa các giá trị trong .env cho khớp máy của bạn
 *
 * Các giá trị mặc định phía dưới khớp với cấu hình XAMPP tiêu chuẩn,
 * nên dự án vẫn chạy được ngay cả khi bạn chưa tạo file .env.
 */

use App\Core\Env;

return [
    'host'     => Env::get('DB_HOST', 'localhost'),
    'port'     => Env::get('DB_PORT', '3306'),
    'dbname'   => Env::get('DB_NAME', 'todo_schema'),
    'username' => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASS', ''),
];
