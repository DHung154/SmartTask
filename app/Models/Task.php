<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Task Model
 *
 * Handles all database operations for tasks (to-do items):
 * - CRUD operations: Create, Read, Update, Delete
 * - Toggle completion status
 * - User-specific queries (each user sees only their tasks)
 * - L?c, s?p x?p, phï¿½n trang
 * - Xï¿½a m?m (soft delete) - task vï¿½o thï¿½ng rï¿½c thay vï¿½ m?t h?n
 *
 * Security:
 * - All queries include user_id check to prevent unauthorized access
 * - Prepared statements prevent SQL injection
 * - Tï¿½n c?t dï¿½ng d? ORDER BY luï¿½n l?y t? danh sï¿½ch tr?ng, khï¿½ng ghï¿½p chu?i t? input
 */
class Task extends Model
{
    /** S? task hi?n th? trï¿½n m?i trang */
    const PER_PAGE = 10;

    /**
     * Cï¿½c ki?u s?p x?p du?c phï¿½p
     *
     * QUAN TR?NG: khï¿½ng bao gi? ghï¿½p tr?c ti?p $_GET['sort'] vï¿½o cï¿½u SQL.
     * Ch? nh?ng khï¿½a cï¿½ trong m?ng nï¿½y m?i du?c dï¿½ng -> ch?ng SQL injection.
     *
     * M?o MySQL: "(due_date IS NULL)" tr? v? 0 ho?c 1, nï¿½n s?p tang d?n
     * s? d?y cï¿½c task khï¿½ng cï¿½ deadline xu?ng cu?i (NULLS LAST).
     */
    private static $sortOptions = [
        // M?c d?nh: vi?c chua xong lï¿½n tru?c, r?i vi?c quan tr?ng,
        // r?i deadline g?n nh?t, cu?i cï¿½ng lï¿½ task m?i t?o
        'smart'     => 'completed ASC, is_important DESC, (due_date IS NULL) ASC, due_date ASC, created_at DESC',
        'newest'    => 'created_at DESC',
        'oldest'    => 'created_at ASC',
        'due'       => '(due_date IS NULL) ASC, due_date ASC, created_at DESC',
        'important' => 'is_important DESC, created_at DESC',
        'title'     => 'title ASC',
    ];

    /**
     * Nhï¿½n ti?ng Vi?t cho t?ng ki?u s?p x?p (dï¿½ng d? d? vï¿½o dropdown)
     *
     * @return array
     */
    public static function sortLabels()
    {
        return [
            'smart'     => "\u{01AF}u ti\u{00EA}n th\u{00F4}ng minh",
            'newest'    => "M\u{1EDB}i nh\u{1EA5}t",
            'oldest'    => "C\u{0169} nh\u{1EA5}t",
            'due'       => "H\u{1EA1}n ch\u{00F3}t g\u{1EA7}n nh\u{1EA5}t",
            'important' => "Quan tr\u{1ECD}ng tr\u{01B0}\u{1EDB}c",
            'title'     => "T\u{00EA}n A - Z",
        ];
    }

    /**
     * Xï¿½y di?u ki?n WHERE tuong ?ng v?i m?t b? l?c
     *
     * Tï¿½ch riï¿½ng ra dï¿½y d? cï¿½u l?y danh sï¿½ch vï¿½ cï¿½u d?m dï¿½ng CHUNG m?t logic.
     * Tru?c dï¿½y hai ch? vi?t riï¿½ng nï¿½n r?t d? l?ch nhau m?i khi s?a.
     *
     * @param int    $userId
     * @param string $filter
     * @return array [chu?i WHERE, m?ng tham s?]
     */
    private function buildFilter($userId, $filter)
    {
        // deleted_at IS NULL: b? qua cï¿½c task dï¿½ xï¿½a m?m (dang ? thï¿½ng rï¿½c)
        $sql    = "user_id = :user_id AND deleted_at IS NULL";
        $params = [':user_id' => $userId];

        // 1. L?c theo Custom List ID (VD: ?list=5)
        if (is_numeric($filter) && (int)$filter > 0) {
            $sql .= " AND list_id = :list_id";
            $params[':list_id'] = (int)$filter;
            return [$sql, $params];
        }

        // 2. L?c theo cï¿½c tr?ng thï¿½i d?c bi?t
        switch ($filter) {
            case 'important':
                $sql .= " AND is_important = 1";
                break;

            case 'my-day':
            case 'today':
                $sql .= " AND due_date = CURDATE()";
                break;

            case 'planned':
                $sql .= " AND due_date IS NOT NULL";
                break;

            case 'overdue':
                // Quï¿½ h?n = cï¿½ deadline, deadline dï¿½ qua, vï¿½ chua hoï¿½n thï¿½nh
                $sql .= " AND due_date IS NOT NULL AND due_date < CURDATE() AND completed = 0";
                break;

            case 'completed':
                $sql .= " AND completed = 1";
                break;

            case 'incomplete':
                $sql .= " AND completed = 0";
                break;

            case 'all':
                // Khï¿½ng thï¿½m di?u ki?n gï¿½ - l?y t?t c? task c?a user
                break;

            case 'trash':
                // Thï¿½ng rï¿½c: d?o ngu?c di?u ki?n, ch? l?y task ï¿½ï¿½ xï¿½a m?m
                $sql = "user_id = :user_id AND deleted_at IS NOT NULL";
                break;

            default:
                // M?c d?nh (Inbox): task khï¿½ng thu?c list nï¿½o.
                // Check c? NULL l?n 0 vï¿½ m?t s? b?n ghi cu luu 0 thay vï¿½ NULL.
                $sql .= " AND (list_id IS NULL OR list_id = 0)";
                break;
        }

        return [$sql, $params];
    }

    /**
     * Chu?n hï¿½a ki?u s?p x?p: input l? thï¿½ tr? v? m?c d?nh
     *
     * @param string|null $sort
     * @return string Khï¿½a h?p l? trong self::$sortOptions
     */
    public static function normalizeSort($sort)
    {
        return isset(self::$sortOptions[$sort]) ? $sort : 'smart';
    }

    /**
     * Get all tasks for a specific user (khï¿½ng phï¿½n trang)
     *
     * @param int $userId User's ID from session
     * @return array Array of task records
     */
    public function getAllByUser($userId)
    {
        $stmt = $this->getDb()->prepare("
            SELECT * FROM tasks
            WHERE user_id = :user_id AND deleted_at IS NULL
            ORDER BY created_at DESC
        ");

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * L?y task c?a user theo b? l?c, cï¿½ s?p x?p vï¿½ phï¿½n trang
     *
     * @param int    $userId
     * @param string $filter  B? l?c (inbox, important, overdue, ... ho?c list_id)
     * @param string $sort    Ki?u s?p x?p (xem self::$sortOptions)
     * @param int    $page    Trang hi?n t?i, b?t d?u t? 1
     * @param int    $perPage S? dï¿½ng m?i trang
     * @return array
     */
    public function getTasksByUserId($userId, $filter = 'inbox', $sort = 'smart', $page = 1, $perPage = self::PER_PAGE)
    {
        list($where, $params) = $this->buildFilter($userId, $filter);

        $orderBy = self::$sortOptions[self::normalizeSort($sort)];

        // LIMIT/OFFSET khï¿½ng bind du?c b?ng placeholder khi dï¿½ t?t emulate prepares,
        // nï¿½n ï¿½p ki?u (int) r?i ghï¿½p th?ng. Sau (int) thï¿½ khï¿½ng th? injection.
        $perPage = max(1, (int)$perPage);
        $page    = max(1, (int)$page);
        $offset  = ($page - 1) * $perPage;

        $sql = "SELECT * FROM tasks WHERE {$where} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * ï¿½?m t?ng s? task kh?p b? l?c (ph?c v? phï¿½n trang vï¿½ sidebar)
     *
     * @param int    $userId
     * @param string $filter
     * @return int
     */
    public function countByFilter($userId, $filter = 'inbox')
    {
        list($where, $params) = $this->buildFilter($userId, $filter);

        $stmt = $this->getDb()->prepare("SELECT COUNT(*) AS total FROM tasks WHERE {$where}");
        $stmt->execute($params);
        $result = $stmt->fetch();

        return (int)($result['total'] ?? 0);
    }

    /**
     * Tï¿½nh t?ng s? trang cho m?t b? l?c
     *
     * @param int    $userId
     * @param string $filter
     * @param int    $perPage
     * @return int ï¿½t nh?t lï¿½ 1 (k? c? khi khï¿½ng cï¿½ task nï¿½o)
     */
    public function totalPages($userId, $filter = 'inbox', $perPage = self::PER_PAGE)
    {
        $perPage = max(1, (int)$perPage);
        $total   = $this->countByFilter($userId, $filter);

        return max(1, (int)ceil($total / $perPage));
    }

    /**
     * Find a task by ID (with user ownership check)
     *
     * Important: Always includes user_id in WHERE clause
     * This prevents users from accessing other users' tasks
     *
     * @param int  $id             Task ID
     * @param int  $userId         User ID (for security)
     * @param bool $includeDeleted Cho phï¿½p l?y c? task trong thï¿½ng rï¿½c (dï¿½ng khi khï¿½i ph?c)
     * @return array|false Task data or false if not found/unauthorized
     */
    public function findById($id, $userId, $includeDeleted = false)
    {
        $sql = "SELECT * FROM tasks WHERE id = :id AND user_id = :user_id";

        if (!$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }

        $sql .= " LIMIT 1";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Create a new task
     *
     * New tasks start with completed = false
     *
     * @param array $data Task data (user_id, title, description)
     * @return int|false Last inserted ID on success, false on failure
     */
    public function create($data)
    {
        $sql = "INSERT INTO tasks (user_id, list_id, title, description, attachment_path, attachment_name, is_important, priority, progress, due_date, completed, created_at)
                VALUES (:user_id, :list_id, :title, :description, :attachment_path, :attachment_name, :is_important, :priority, :progress, :due_date, :completed, NOW())";

        $stmt = $this->getDb()->prepare($sql);

        $stmt->execute([
            ':user_id'      => $data['user_id'],
            ':list_id'      => $data['list_id'] ?? null,      // N?u khï¿½ng cï¿½ thï¿½ lï¿½ NULL
            ':title'        => $data['title'],
            ':description'  => $data['description'] ?? '',
            ':attachment_path' => $data['attachment_path'] ?? null,
            ':attachment_name' => $data['attachment_name'] ?? null,
            ':is_important' => $data['is_important'] ?? 0,
            ':priority'     => $data['priority'] ?? 'normal',
            ':progress'     => max(0, min(100, (int)($data['progress'] ?? 0))),
            ':due_date'     => $data['due_date'] ?? null,
            ':completed'    => (int)(($data['progress'] ?? 0) >= 100)
        ]);

        return $this->getDb()->lastInsertId();
    }

    /**
     * Update an existing task
     *
     * Updates title, description, list, importance, and due date
     * Completion status is handled separately by toggleComplete()
     *
     * @param int   $id     Task ID
     * @param array $data   Updated task data
     * @param int   $userId User ID (for security check)
     * @return bool True on success
     */
    public function update($id, $data, $userId)
    {
        $sql = "UPDATE tasks
                SET title = :title,
                    description = :description,
                    attachment_path = :attachment_path,
                    attachment_name = :attachment_name,
                    list_id = :list_id,
                    is_important = :is_important,
                    priority = :priority,
                    progress = :progress,
                    completed = :completed,
                    due_date = :due_date
                WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL";

        $stmt = $this->getDb()->prepare($sql);

        return $stmt->execute([
            ':title'        => $data['title'],
            ':description'  => $data['description'] ?? '',
            ':attachment_path' => $data['attachment_path'] ?? null,
            ':attachment_name' => $data['attachment_name'] ?? null,
            ':list_id'      => $data['list_id'] ?? null,
            ':is_important' => $data['is_important'] ?? 0,
            ':priority'     => $data['priority'] ?? 'normal',
            ':progress'     => max(0, min(100, (int)($data['progress'] ?? 0))),
            ':completed'    => (int)(($data['progress'] ?? 0) >= 100),
            ':due_date'     => $data['due_date'] ?? null,
            ':id'           => $id,
            ':user_id'      => $userId
        ]);
    }

    /**
     * Xï¿½a m?m m?t task (dua vï¿½o thï¿½ng rï¿½c)
     *
     * Vï¿½ sao dï¿½ng xï¿½a m?m?
     * - Ngu?i dï¿½ng l? tay xï¿½a v?n khï¿½i ph?c du?c
     * - D? li?u khï¿½ng m?t vinh vi?n ch? vï¿½ m?t cï¿½ click nh?m
     * B?n ghi v?n n?m trong b?ng, ch? lï¿½ m?i truy v?n d?u l?c deleted_at IS NULL.
     *
     * @param int $id     Task ID
     * @param int $userId User ID (for security check)
     * @return bool True on success
     */
    public function delete($id, $userId)
    {
        $stmt = $this->getDb()->prepare("
            UPDATE tasks
            SET deleted_at = NOW()
            WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
        ");

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        // rowCount() > 0 nghia lï¿½ th?c s? cï¿½ b?n ghi b? d?i.
        // execute() tr? v? true c? khi khï¿½ng kh?p dï¿½ng nï¿½o (VD: id c?a ngu?i khï¿½c),
        // nï¿½n ph?i check thï¿½m m?i bï¿½o "thï¿½nh cï¿½ng" dï¿½ng s? th?t.
        return $stmt->rowCount() > 0;
    }

    /**
     * Khï¿½i ph?c task t? thï¿½ng rï¿½c
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function restore($id, $userId)
    {
        $stmt = $this->getDb()->prepare("
            UPDATE tasks
            SET deleted_at = NULL
            WHERE id = :id AND user_id = :user_id AND deleted_at IS NOT NULL
        ");

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Xï¿½a vinh vi?n (ch? ï¿½p d?ng cho task dï¿½ n?m trong thï¿½ng rï¿½c)
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function forceDelete($id, $userId)
    {
        $stmt = $this->getDb()->prepare("
            DELETE FROM tasks
            WHERE id = :id AND user_id = :user_id AND deleted_at IS NOT NULL
        ");

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * D?n s?ch thï¿½ng rï¿½c c?a m?t user
     *
     * @param int $userId
     * @return int S? task dï¿½ b? xï¿½a h?n
     */
    public function emptyTrash($userId)
    {
        $stmt = $this->getDb()->prepare("
            DELETE FROM tasks
            WHERE user_id = :user_id AND deleted_at IS NOT NULL
        ");

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Toggle task completion status
     *
     * How it works:
     * - If completed = true, set to false
     * - If completed = false, set to true
     *
     * @param int $id     Task ID
     * @param int $userId User ID (for security check)
     * @return bool True on success
     */
    public function toggleComplete($id, $userId)
    {
        $stmt = $this->getDb()->prepare("
            UPDATE tasks
            SET progress = IF(completed = 1, 0, 100),
                completed = NOT completed
            WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
        ");

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * ï¿½?o tr?ng thï¿½i quan tr?ng (Ngï¿½i sao)
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function updateProgress($id, $userId, $progress)
    {
        $progress = max(0, min(100, (int)$progress));
        $stmt = $this->getDb()->prepare("
            UPDATE tasks
            SET progress = :progress, completed = :completed
            WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
        ");
        $stmt->execute([
            ':progress' => $progress,
            ':completed' => (int)($progress === 100),
            ':id' => $id,
            ':user_id' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function toggleImportant($id, $userId)
    {
        $stmt = $this->getDb()->prepare("
            UPDATE tasks
            SET is_important = NOT is_important
            WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
        ");

        $stmt->execute([':id' => $id, ':user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Get count of tasks for all special filters and lists for a user
     *
     * Dï¿½ng d? hi?n con s? bï¿½n c?nh m?i m?c trong sidebar.
     *
     * @param int   $userId    User ID
     * @param array $userLists Array of user's custom lists
     * @return array Associative array with counts for each filter/list
     */
    public function getTaskCounts($userId, $userLists = [])
    {
        $counts = [
            'inbox'      => $this->countByFilter($userId, 'inbox'),
            'my-day'     => $this->countByFilter($userId, 'my-day'),
            'important'  => $this->countByFilter($userId, 'important'),
            'planned'    => $this->countByFilter($userId, 'planned'),
            'overdue'    => $this->countByFilter($userId, 'overdue'),
            'completed'  => $this->countByFilter($userId, 'completed'),
            'incomplete' => $this->countByFilter($userId, 'incomplete'),
            'trash'      => $this->countByFilter($userId, 'trash'),
            'lists'      => []
        ];

        foreach ($userLists as $list) {
            $counts['lists'][$list['id']] = $this->countByFilter($userId, $list['id']);
        }

        return $counts;
    }

    /**
     * Get comprehensive task statistics for a user
     *
     * Gom t?t c? s? li?u vï¿½o M?T cï¿½u query thay vï¿½ ch?y nhi?u cï¿½u riï¿½ng l?.
     *
     * @param int $userId User ID
     * @return array Statistics including total, completed, incomplete, important, overdue
     */
    public function getStatistics($userId)
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN completed = 0 THEN 1 ELSE 0 END) AS incomplete,
                    SUM(CASE WHEN is_important = 1 THEN 1 ELSE 0 END) AS important,
                    SUM(CASE WHEN due_date IS NOT NULL
                              AND due_date < CURDATE()
                              AND completed = 0 THEN 1 ELSE 0 END) AS overdue,
                    SUM(CASE WHEN due_date = CURDATE() THEN 1 ELSE 0 END) AS due_today,
                    MIN(created_at) AS first_task_date,
                    MAX(created_at) AS last_task_date
                FROM tasks
                WHERE user_id = :user_id AND deleted_at IS NULL";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();

        $total     = (int)($result['total'] ?? 0);
        $completed = (int)($result['completed'] ?? 0);

        return [
            'total'           => $total,
            'completed'       => $completed,
            'incomplete'      => (int)($result['incomplete'] ?? 0),
            'important'       => (int)($result['important'] ?? 0),
            'overdue'         => (int)($result['overdue'] ?? 0),
            'due_today'       => (int)($result['due_today'] ?? 0),
            // Trï¿½nh chia cho 0 khi user chua cï¿½ task nï¿½o
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'first_task_date' => $result['first_task_date'] ?? null,
            'last_task_date'  => $result['last_task_date'] ?? null
        ];
    }

    /**
     * Search tasks by title or description
     *
     * @param int    $userId  User's ID
     * @param string $query   Search query
     * @param string $sort    Ki?u s?p x?p
     * @param int    $page    Trang hi?n t?i
     * @param int    $perPage S? dï¿½ng m?i trang
     * @return array Array of matching tasks
     */
    public function searchTasks($userId, $query, $sort = 'smart', $page = 1, $perPage = self::PER_PAGE)
    {
        $orderBy = self::$sortOptions[self::normalizeSort($sort)];

        $perPage = max(1, (int)$perPage);
        $page    = max(1, (int)$page);
        $offset  = ($page - 1) * $perPage;

        $sql = "SELECT * FROM tasks
                WHERE user_id = :user_id
                  AND deleted_at IS NULL
                  AND (title LIKE :title_term OR description LIKE :description_term)
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            // escapeLike() d? ngu?i dï¿½ng gï¿½ % hay _ khï¿½ng phï¿½ m?t cï¿½u tï¿½m ki?m
            ':title_term'       => '%' . $this->escapeLike($query) . '%',
            ':description_term' => '%' . $this->escapeLike($query) . '%'
        ]);

        return $stmt->fetchAll();
    }

    /**
     * ï¿½?m s? k?t qu? tï¿½m ki?m (ph?c v? phï¿½n trang)
     *
     * @param int    $userId
     * @param string $query
     * @return int
     */
    public function countSearchResults($userId, $query)
    {
        $stmt = $this->getDb()->prepare("
            SELECT COUNT(*) AS total FROM tasks
            WHERE user_id = :user_id
              AND deleted_at IS NULL
              AND (title LIKE :title_term OR description LIKE :description_term)
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':title_term'       => '%' . $this->escapeLike($query) . '%',
            ':description_term' => '%' . $this->escapeLike($query) . '%'
        ]);

        $result = $stmt->fetch();

        return (int)($result['total'] ?? 0);
    }

    public function getTasksBetweenDates($userId, $startDate, $endDate)
    {
        $stmt = $this->getDb()->prepare("
            SELECT * FROM tasks
            WHERE user_id = :user_id
              AND deleted_at IS NULL
              AND due_date BETWEEN :start_date AND :end_date
            ORDER BY due_date ASC, completed ASC, priority DESC, created_at DESC
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);

        return $stmt->fetchAll();
    }

    public function getPriorityCounts($userId)
    {
        $stmt = $this->getDb()->prepare("
            SELECT priority, COUNT(*) AS total
            FROM tasks
            WHERE user_id = :user_id AND deleted_at IS NULL
            GROUP BY priority
        ");
        $stmt->execute([':user_id' => $userId]);

        return array_column($stmt->fetchAll(), 'total', 'priority');
    }

    public function getMonthlySummary($userId, $months = 6)
    {
        $months = max(1, min(12, (int)$months));
        $stmt = $this->getDb()->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                   COUNT(*) AS total,
                   SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) AS completed
            FROM tasks
            WHERE user_id = :user_id
              AND deleted_at IS NULL
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL {$months} MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function getOverdueTasksForReminders()
    {
        $stmt = $this->getDb()->query("
            SELECT tasks.*, users.email, users.name
            FROM tasks
            INNER JOIN users ON users.id = tasks.user_id
            WHERE tasks.deleted_at IS NULL
              AND tasks.completed = 0
              AND tasks.due_date IS NOT NULL
              AND tasks.due_date < CURDATE()
              AND (tasks.reminder_sent_at IS NULL OR DATE(tasks.reminder_sent_at) < CURDATE())
              AND (tasks.reminder_queued_at IS NULL OR DATE(tasks.reminder_queued_at) < CURDATE())
            ORDER BY users.id ASC, tasks.due_date ASC
        ");

        return $stmt->fetchAll();
    }

    public function markReminderSent($taskId)
    {
        $stmt = $this->getDb()->prepare("UPDATE tasks SET reminder_sent_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $taskId]);
    }

    public function markReminderQueued($taskId)
    {
        $stmt = $this->getDb()->prepare("UPDATE tasks SET reminder_queued_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $taskId]);
    }

    /**
     * Vï¿½ hi?u hï¿½a cï¿½c kï¿½ t? d?c bi?t c?a LIKE
     *
     * Trong LIKE, "%" nghia lï¿½ "kh?p m?i th?" vï¿½ "_" lï¿½ "kh?p 1 kï¿½ t? b?t k?".
     * N?u ngu?i dï¿½ng gï¿½ "50%" mï¿½ khï¿½ng escape thï¿½ k?t qu? s? sai bï¿½t.
     *
     * @param string $value
     * @return string
     */
    private function escapeLike($value)
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], (string)$value);
    }
}

