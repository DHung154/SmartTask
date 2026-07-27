<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamInvitation extends Model
{
    protected $fillable = ['team_id', 'user_id', 'invited_by', 'role', 'status', 'responded_at'];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Lời mời đang chờ của user, kèm tên nhóm và người mời (dùng cho chuông thông báo).
     */
    public static function pendingFor($userId)
    {
        return static::pending()
            ->where('user_id', $userId)
            ->with(['team:id,name', 'inviter:id,name'])
            ->orderByDesc('created_at')
            ->get();
    }
}
