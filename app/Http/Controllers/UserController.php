<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Task;
use App\Models\TodoList;

class UserController extends Controller
{
    public function profile()
    {
        $userId = auth()->id();
        $user = User::find($userId);

        if (!$user) {
            Auth::logout();
            return redirect('/login');
        }

        $userLists = TodoList::where('user_id', $userId)->orderBy('created_at')->get();

        return view('user.profile', [
            'title'      => 'Tài khoản của tôi',
            'user'       => $user,
            'userLists'  => $userLists,
            'taskCounts' => Task::getTaskCounts($userId, $userLists),
            'stats'      => Task::getStatistics($userId),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $userId = auth()->id();
        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));

        $errors = [];

        if ($name === '') $errors['name'] = 'Vui lòng nhập họ tên.';
        elseif (mb_strlen($name) > 100) $errors['name'] = 'Họ tên không được vượt quá 100 ký tự.';

        if ($email === '') $errors['email'] = 'Vui lòng nhập email.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email không đúng định dạng.';
        elseif (mb_strlen($email) > 255) $errors['email'] = 'Email quá dài.';
        elseif (User::where('email', $email)->where('id', '!=', $userId)->exists()) {
            $errors['email'] = 'Email này đã được người khác sử dụng.';
        }

        if ($errors) {
            return redirect('/profile')->withErrors($errors)->withInput();
        }

        $user = User::find($userId);
        $updated = $user->update(['name' => $name, 'email' => $email]);

        if ($updated) {
            session()->flash('success', 'Đã cập nhật thông tin tài khoản.');
        } else {
            session()->flash('error', 'Không cập nhật được thông tin. Vui lòng thử lại.');
        }

        return redirect('/profile');
    }

    public function changePassword(Request $request)
    {
        $userId = auth()->id();
        $current = $request->input('current_password', '');
        $new = $request->input('new_password', '');
        $confirm = $request->input('confirm_password', '');

        $user = User::find($userId);
        $errors = [];

        if ($current === '') $errors['current_password'] = 'Vui lòng nhập mật khẩu hiện tại.';
        elseif (!Hash::check($current, $user->password)) $errors['current_password'] = 'Mật khẩu hiện tại không đúng.';

        if ($new === '') $errors['new_password'] = 'Vui lòng nhập mật khẩu mới.';
        elseif (strlen($new) < 6) $errors['new_password'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        elseif ($new === $current) $errors['new_password'] = 'Mật khẩu mới phải khác mật khẩu cũ.';

        if ($confirm === '') $errors['confirm_password'] = 'Vui lòng nhập lại mật khẩu mới.';
        elseif ($new !== $confirm) $errors['confirm_password'] = 'Mật khẩu nhập lại không khớp.';

        if ($errors) {
            session()->flash('open_tab', 'security');
            return redirect('/profile')->withErrors($errors)->withInput();
        }

        $updated = $user->update(['password' => Hash::make($new)]);

        if ($updated) {
            $request->session()->regenerate();
            session()->flash('success', 'Đã đổi mật khẩu thành công.');
        } else {
            session()->flash('error', 'Không đổi được mật khẩu. Vui lòng thử lại.');
        }

        return redirect('/profile');
    }
}
