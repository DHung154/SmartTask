<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login', ['title' => 'Đăng nhập']);
    }

    public function login(Request $request)
    {
        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');

        $errors = [];
        if ($email === '') $errors['email'] = 'Vui lòng nhập email.';
        if ($password === '') $errors['password'] = 'Vui lòng nhập mật khẩu.';

        if ($errors) {
            return redirect('/login')->withErrors($errors)->withInput();
        }

        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            session()->flash('error', 'Email hoặc mật khẩu không đúng.');
            return redirect('/login')->withInput(['email' => $email]);
        }

        $request->session()->regenerate();
        $user = Auth::user();
        session()->flash('success', 'Chào mừng trở lại, ' . $user->name . '!');
        return redirect('/');
    }

    public function showRegister()
    {
        return view('auth.register', ['title' => 'Đăng ký']);
    }

    public function register(Request $request)
    {
        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');
        $confirmPassword = $request->input('confirm_password', '');

        $errors = [];

        if ($name === '') $errors['name'] = 'Vui lòng nhập họ tên.';
        elseif (mb_strlen($name) > 100) $errors['name'] = 'Họ tên không được vượt quá 100 ký tự.';

        if ($email === '') $errors['email'] = 'Vui lòng nhập email.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email không đúng định dạng.';
        elseif (mb_strlen($email) > 255) $errors['email'] = 'Email quá dài.';
        elseif (User::where('email', $email)->exists()) $errors['email'] = 'Email này đã được đăng ký.';

        if ($password === '') $errors['password'] = 'Vui lòng nhập mật khẩu.';
        elseif (strlen($password) < 6) $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';

        if ($confirmPassword === '') $errors['confirm_password'] = 'Vui lòng nhập lại mật khẩu.';
        elseif ($password !== $confirmPassword) $errors['confirm_password'] = 'Mật khẩu nhập lại không khớp.';

        if ($errors) {
            return redirect('/register')->withErrors($errors)->withInput();
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        if ($user) {
            session()->flash('success', 'Đăng ký thành công! Mời bạn đăng nhập.');
            return redirect('/login');
        }

        return redirect('/register')->withErrors([], 'Đăng ký thất bại. Vui lòng thử lại.')->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
