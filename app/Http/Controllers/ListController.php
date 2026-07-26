<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\TodoList;
use App\Models\Task;

class ListController extends Controller
{
    public function create()
    {
        return view('lists.create', $this->baseData() + [
            'title' => 'Danh sách mới',
        ]);
    }

    public function store(Request $request)
    {
        $userId = auth()->id();
        $name = trim($request->input('name', ''));

        $errors = $this->validateName($name);
        if ($errors) {
            return redirect('/lists/create')->withErrors($errors)->withInput();
        }

        $list = TodoList::create([
            'user_id' => $userId,
            'name'    => $name,
        ]);

        Cache::forget('dashboard-user-' . $userId);
        session()->flash('success', 'Đã tạo danh sách "' . $name . '".');

        return redirect($list ? '/tasks?list=' . $list->id : '/');
    }

    public function edit(Request $request)
    {
        $userId = auth()->id();
        $id = $request->query('id');
        $list = TodoList::where('id', $id)->where('user_id', $userId)->first();

        if (!$list) {
            session()->flash('error', 'Không tìm thấy danh sách.');
            return redirect('/');
        }

        return view('lists.edit', $this->baseData() + [
            'title' => 'Sửa danh sách',
            'list'  => $list,
        ]);
    }

    public function update(Request $request)
    {
        $userId = auth()->id();
        $id = $request->input('id');
        $list = TodoList::where('id', $id)->where('user_id', $userId)->first();

        if (!$list) {
            session()->flash('error', 'Không tìm thấy danh sách.');
            return redirect('/');
        }

        $name = trim($request->input('name', ''));
        $errors = $this->validateName($name);
        if ($errors) {
            return redirect('/lists/edit?id=' . urlencode($id))->withErrors($errors)->withInput();
        }

        $list->update(['name' => $name]);
        Cache::forget('dashboard-user-' . $userId);

        session()->flash('success', 'Đã đổi tên danh sách.');
        return redirect('/tasks?list=' . $id);
    }

    public function destroy(Request $request)
    {
        $userId = auth()->id();
        $id = $request->input('id');
        $list = $id ? TodoList::where('id', $id)->where('user_id', $userId)->first() : null;

        if (!$list) {
            session()->flash('error', 'Không tìm thấy danh sách hoặc bạn không có quyền xóa.');
        } else {
            $name = $list->name;
            $list->delete();
            Cache::forget('dashboard-user-' . $userId);
            session()->flash('success', 'Đã xóa danh sách "' . $name . '".');
        }

        return redirect('/');
    }

    private function baseData(): array
    {
        $userId = auth()->id();
        $userLists = TodoList::where('user_id', $userId)->orderBy('created_at')->get();
        return [
            'userLists'  => $userLists,
            'taskCounts' => Task::getTaskCounts($userId, $userLists),
        ];
    }

    private function validateName(string $name): array
    {
        if ($name === '') {
            return ['name' => 'Vui lòng nhập tên danh sách.'];
        }
        if (mb_strlen($name) > 100) {
            return ['name' => 'Tên danh sách không được vượt quá 100 ký tự.'];
        }
        return [];
    }
}
