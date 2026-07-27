<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Models\User;

class PasswordResetController extends Controller
{
    public function showForgot()
    {
        return view('auth.forgot-password', ['title' => 'Quên mật khẩu']);
    }

    /**
     * Gửi link đặt lại mật khẩu. Luôn báo cùng một thông điệp dù email
     * có tồn tại hay không, để không lộ email nào đã đăng ký.
     */
    public function sendLink(Request $request)
    {
        $email = trim($request->input('email', ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect('/forgot-password')
                ->withErrors(['email' => 'Email không đúng định dạng.'])
                ->withInput();
        }

        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_THROTTLED) {
            return redirect('/forgot-password')
                ->withErrors(['email' => 'Bạn vừa yêu cầu rồi, đợi một phút hãy thử lại.'])
                ->withInput();
        }

        session()->flash('success', 'Nếu email này đã đăng ký, chúng tôi đã gửi link đặt lại mật khẩu. Hãy kiểm tra hộp thư.');
        return redirect('/login');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'title' => 'Đặt lại mật khẩu',
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');
        $confirm = $request->input('password_confirmation', '');
        $token = $request->input('token', '');

        $errors = [];
        if ($email === '') $errors['email'] = 'Vui lòng nhập email.';
        if ($password === '') $errors['password'] = 'Vui lòng nhập mật khẩu mới.';
        elseif (strlen($password) < 6) $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        if ($password !== $confirm) $errors['password_confirmation'] = 'Mật khẩu nhập lại không khớp.';

        if ($errors) {
            return redirect('/reset-password/' . $token . '?email=' . urlencode($email))
                ->withErrors($errors);
        }

        $status = Password::reset(
            [
                'email'                 => $email,
                'password'              => $password,
                'password_confirmation' => $confirm,
                'token'                 => $token,
            ],
            // Bảng users của dự án không có cột remember_token nên chỉ ghi mật khẩu.
            function (User $user, string $newPassword) {
                $user->forceFill(['password' => $newPassword])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('success', 'Đổi mật khẩu thành công. Mời bạn đăng nhập.');
            return redirect('/login');
        }

        $message = match ($status) {
            Password::INVALID_TOKEN => 'Link đặt lại đã hết hạn hoặc không hợp lệ. Hãy yêu cầu link mới.',
            Password::INVALID_USER  => 'Không tìm thấy tài khoản với email này.',
            default                 => 'Không đặt lại được mật khẩu. Hãy thử lại.',
        };

        return redirect('/forgot-password')->withErrors(['email' => $message]);
    }
}
