<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'message'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log($userId, $action, $entityType, $entityId, $message)
    {
        return static::create([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'message'     => mb_substr($message, 0, 255),
        ]);
    }
}
