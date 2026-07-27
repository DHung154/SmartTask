<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subtask extends Model
{
    protected $fillable = ['task_id', 'title', 'completed', 'position'];

    protected $casts = [
        'completed' => 'boolean',
        'position'  => 'integer',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
