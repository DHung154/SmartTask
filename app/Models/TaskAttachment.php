<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskAttachment extends Model
{
    protected $fillable = ['task_id', 'user_id', 'path', 'name', 'size'];

    protected $casts = [
        'size' => 'integer',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Đường dẫn tuyệt đối trên đĩa, để xoá file vật lý. */
    public function absolutePath(): string
    {
        return public_path(ltrim($this->path, '/'));
    }

    /** Xoá file trên đĩa rồi xoá bản ghi. */
    public function deleteWithFile(): void
    {
        $file = $this->absolutePath();
        if ($this->path && is_file($file)) {
            @unlink($file);
        }
        $this->delete();
    }

    public function isImage(): bool
    {
        return in_array(strtolower(pathinfo($this->name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'], true);
    }

    public function humanSize(): string
    {
        if ($this->size <= 0) return '';
        return $this->size >= 1048576
            ? round($this->size / 1048576, 1) . ' MB'
            : max(1, round($this->size / 1024)) . ' KB';
    }
}
