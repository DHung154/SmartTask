<?php

namespace App\Models;

use App\Core\Model;

class Team extends Model
{
    protected string $table = 'teams';

    public function __construct()
    {
        parent::__construct(); // Khởi tạo kết nối database từ lớp cha Model
    }

    public function create(array $data)
    {
        // Gọi hàm getDb() từ Model cha để lấy PDO
        $stmt = $this->getDb()->prepare("
            INSERT INTO teams (name, description, owner_id, created_at) 
            VALUES (:name, :description, :owner_id, NOW())
        ");

        return $stmt->execute([
            ':name'        => $data['name'],
            ':description' => $data['description'] ?? null,
            ':owner_id'    => $data['owner_id']
        ]);
    }

    public function getTeamsByUserId($userId)
    {
        // Sử dụng prepared statement an toàn và đồng bộ với kiến trúc framework
        $stmt = $this->getDb()->prepare("SELECT * FROM teams WHERE owner_id = ?");
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}