<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class TaskRecord extends Model
{
    protected $table = 'tasks';
    public $timestamps = false;
    protected $fillable = [
        'user_id', 'list_id', 'title', 'description', 'is_important',
        'priority', 'progress', 'due_date', 'completed',
    ];

    protected $hidden = [
        'attachment_path', 'attachment_name', 'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'list_id' => 'integer',
            'is_important' => 'boolean',
            'completed' => 'boolean',
            'progress' => 'integer',
            'due_date' => 'date:Y-m-d',
        ];
    }
}
