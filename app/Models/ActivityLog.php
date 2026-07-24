<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ActivityLog extends Model
{
    public function log($userId, $action, $entityType, $entityId, $message)
    {
        $stmt = $this->getDb()->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, message, created_at)
            VALUES (:user_id, :action, :entity_type, :entity_id, :message, NOW())
        ");

        return $stmt->execute([
            ':user_id'     => $userId,
            ':action'      => $action,
            ':entity_type' => $entityType,
            ':entity_id'   => $entityId,
            ':message'     => mb_substr($message, 0, 255),
        ]);
    }

    public function latest($userId, $limit = 30)
    {
        $limit = max(1, min(100, (int)$limit));
        $stmt = $this->getDb()->prepare("
            SELECT * FROM activity_logs
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT {$limit}
        ");
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
