<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * TodoList Model
 * Qu?n l� c�c danh s�ch c�ng vi?c t�y ch?nh (Custom Lists)
 */
class TodoList extends Model
{
    /**
     * L?y t?t c? danh s�ch c?a m?t user c? th?
     * S?p x?p theo ng�y t?o tang d?n (cu nh?t l�n tru?c)
     */
    public function getListsByUserId($userId)
    {
        $stmt = $this->getDb()->prepare("
            SELECT * FROM lists 
            WHERE user_id = :user_id 
            ORDER BY created_at ASC
        ");
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * T�m danh s�ch theo ID (k�m ki?m tra quy?n s? h?u user_id)
     * D�ng d? verify tru?c khi S?a ho?c X�a
     */
    public function findById($id, $userId)
    {
        $stmt = $this->getDb()->prepare("
            SELECT * FROM lists 
            WHERE id = :id AND user_id = :user_id 
            LIMIT 1
        ");
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * T?o danh s�ch m?i
     */
    public function create($userId, $name)
    {
        $sql = "INSERT INTO lists (user_id, name, created_at) 
                VALUES (:user_id, :name, NOW())";

        $stmt = $this->getDb()->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId,
            ':name'    => $name
        ]);

        return $this->getDb()->lastInsertId();
    }

    /**
     * C?p nh?t t�n danh s�ch
     */
    public function update($id, $userId, $name)
    {
        $sql = "UPDATE lists 
                SET name = :name 
                WHERE id = :id AND user_id = :user_id";

        $stmt = $this->getDb()->prepare($sql);

        return $stmt->execute([
            ':name'    => $name,
            ':id'      => $id,
            ':user_id' => $userId
        ]);
    }

    /**
     * X�a danh s�ch
     * Luu �: C�c task trong list n�y s? b? set list_id = NULL ho?c b? x�a 
     * t�y theo c?u h�nh kh�a ngo?i (ON DELETE SET NULL/CASCADE) trong DB.
     */
    public function delete($id, $userId)
    {
        $stmt = $this->getDb()->prepare("
            DELETE FROM lists 
            WHERE id = :id AND user_id = :user_id
        ");

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}