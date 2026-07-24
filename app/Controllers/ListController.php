<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Cache;
use App\Core\Session;
use App\Models\TodoList;
use App\Models\Task;

class ListController extends Controller
{
    private $listModel;
    private $taskModel;

    public function __construct()
    {
        $this->listModel = new TodoList();
        $this->taskModel = new Task();
    }

    public function create()
    {
        $this->requireAuth();
        $userId = Session::get('user_id');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf('/lists/create');
            $name = trim($_POST['name'] ?? '');
            if ($errors = $this->validateName($name)) {
                $this->backWithErrors('/lists/create', $errors);
                return;
            }
            $newId = $this->listModel->create($userId, $name);
            Cache::forgetDashboard((int) $userId);
            Session::flash('success', "\u{0110}\u{00E3} t\u{1EA1}o danh s\u{00E1}ch \"" . $name . '".');
            $this->redirect($newId ? '/tasks?list=' . $newId : '/');
            return;
        }
        $this->view('lists/create', $this->baseData($userId) + ['title' => "Danh s\u{00E1}ch m\u{1EDB}i"]);
    }

    public function edit()
    {
        $this->requireAuth();
        $userId = Session::get('user_id');
        $id = $_GET['id'] ?? null;
        $list = $this->listModel->findById($id, $userId);
        if (!$list) {
            Session::flash('error', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y danh s\u{00E1}ch.");
            $this->redirect('/');
            return;
        }
        $backUrl = '/lists/edit?id=' . urlencode($id);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf($backUrl);
            $name = trim($_POST['name'] ?? '');
            if ($errors = $this->validateName($name)) {
                $this->backWithErrors($backUrl, $errors);
                return;
            }
            $this->listModel->update($id, $userId, $name);
            Cache::forgetDashboard((int) $userId);
            Session::flash('success', "\u{0110}\u{00E3} \u{0111}\u{1ED5}i t\u{00EA}n danh s\u{00E1}ch.");
            $this->redirect('/tasks?list=' . $id);
            return;
        }
        $this->view('lists/edit', $this->baseData($userId) + ['title' => "S\u{1EED}a danh s\u{00E1}ch", 'list' => $list]);
    }

    public function delete()
    {
        $this->requireAuth();
        $this->requirePost();
        $this->requireCsrf('/');
        $userId = Session::get('user_id');
        $id = $_POST['id'] ?? null;
        $list = $id ? $this->listModel->findById($id, $userId) : false;
        if (!$list) {
            Session::flash('error', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y danh s\u{00E1}ch ho\u{1EB7}c b\u{1EA1}n kh\u{00F4}ng c\u{00F3} quy\u{1EC1}n x\u{00F3}a.");
        } elseif ($this->listModel->delete($id, $userId)) {
            Cache::forgetDashboard((int) $userId);
            Session::flash('success', "\u{0110}\u{00E3} x\u{00F3}a danh s\u{00E1}ch \"" . $list['name'] . '".');
        } else {
            Session::flash('error', "Kh\u{00F4}ng x\u{00F3}a \u{0111}\u{01B0}\u{1EE3}c danh s\u{00E1}ch.");
        }
        $this->redirect('/');
    }

    private function baseData($userId)
    {
        $userLists = $this->listModel->getListsByUserId($userId);
        return ['userLists' => $userLists, 'taskCounts' => $this->taskModel->getTaskCounts($userId, $userLists)];
    }

    private function validateName($name)
    {
        if ($name === '') return ['name' => "Vui l\u{00F2}ng nh\u{1EAD}p t\u{00EA}n danh s\u{00E1}ch."];
        if (mb_strlen($name) > 100) return ['name' => "T\u{00EA}n danh s\u{00E1}ch kh\u{00F4}ng \u{0111}\u{01B0}\u{1EE3}c v\u{01B0}\u{1EE3}t qu\u{00E1} 100 k\u{00FD} t\u{1EF1}."];
        return [];
    }
}
