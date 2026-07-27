<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password'];

    /**
     * Bảng users không có cột remember_token nên tắt tính năng "ghi nhớ đăng nhập",
     * tránh Laravel cố ghi vào cột không tồn tại.
     */
    public function getRememberTokenName()
    {
        return null;
    }

    protected $casts = [
        'password' => 'hashed',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /** Việc được người khác giao cho mình. */
    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function lists()
    {
        return $this->hasMany(TodoList::class);
    }

    public function ownedTeams()
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function teamMemberships()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members')->withPivot('role')->withTimestamps();
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
