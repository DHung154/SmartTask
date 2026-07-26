<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    const PER_PAGE = 10;

    protected $fillable = [
        'user_id', 'list_id', 'team_id', 'title', 'description',
        'attachment_path', 'attachment_name', 'completed', 'is_important',
        'priority', 'progress', 'due_date', 'reminder_sent_at', 'reminder_queued_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'is_important' => 'boolean',
        'progress' => 'integer',
        'due_date' => 'date',
        'reminder_sent_at' => 'datetime',
        'reminder_queued_at' => 'datetime',
    ];

    protected static $sortOptions = [
        'smart'     => 'completed ASC, is_important DESC, (due_date IS NULL) ASC, due_date ASC, created_at DESC',
        'newest'    => 'created_at DESC',
        'oldest'    => 'created_at ASC',
        'due'       => '(due_date IS NULL) ASC, due_date ASC, created_at DESC',
        'important' => 'is_important DESC, created_at DESC',
        'title'     => 'title ASC',
    ];

    public static function sortLabels(): array
    {
        return [
            'smart'     => 'Ưu tiên thông minh',
            'newest'    => 'Mới nhất',
            'oldest'    => 'Cũ nhất',
            'due'       => 'Hạn chót gần nhất',
            'important' => 'Quan trọng trước',
            'title'     => 'Tên A - Z',
        ];
    }

    public static function normalizeSort($sort): string
    {
        return isset(static::$sortOptions[$sort]) ? $sort : 'smart';
    }

    public static function getSortSql(string $sort): string
    {
        return static::$sortOptions[static::normalizeSort($sort)];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function todoList()
    {
        return $this->belongsTo(TodoList::class, 'list_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    // Query Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeApplyFilter($query, $filter)
    {
        switch ($filter) {
            case 'important':
                return $query->where('is_important', true);
            case 'my-day':
            case 'today':
                return $query->whereDate('due_date', today());
            case 'planned':
                return $query->whereNotNull('due_date');
            case 'overdue':
                return $query->whereNotNull('due_date')
                    ->whereDate('due_date', '<', today())
                    ->where('completed', false);
            case 'completed':
                return $query->where('completed', true);
            case 'incomplete':
                return $query->where('completed', false);
            case 'all':
                return $query;
            case 'trash':
                return $query->onlyTrashed();
            default:
                if (is_numeric($filter) && (int)$filter > 0) {
                    return $query->where('list_id', (int)$filter);
                }
                // inbox: tasks not in any list
                return $query->where(function ($q) {
                    $q->whereNull('list_id')->orWhere('list_id', 0);
                });
        }
    }

    public function scopeApplySort($query, $sort)
    {
        $sortSql = static::getSortSql($sort);
        return $query->orderByRaw($sortSql);
    }

    // Static helper methods
    public static function getFilteredTasks($userId, $filter, $sort, $page, $perPage = self::PER_PAGE)
    {
        $query = static::query()->forUser($userId);

        if ($filter === 'trash') {
            $query = static::onlyTrashed()->forUser($userId);
        } else {
            $query->applyFilter($filter);
        }

        return $query->applySort($sort)->paginate($perPage, ['*'], 'page', $page);
    }

    public static function getTaskCounts($userId, $userLists = [])
    {
        $base = static::query()->forUser($userId);

        $counts = [
            'inbox'      => (clone $base)->applyFilter('inbox')->count(),
            'my-day'     => (clone $base)->applyFilter('my-day')->count(),
            'important'  => (clone $base)->applyFilter('important')->count(),
            'planned'    => (clone $base)->applyFilter('planned')->count(),
            'overdue'    => (clone $base)->applyFilter('overdue')->count(),
            'completed'  => (clone $base)->applyFilter('completed')->count(),
            'incomplete' => (clone $base)->applyFilter('incomplete')->count(),
            'trash'      => static::onlyTrashed()->forUser($userId)->count(),
            'lists'      => [],
        ];

        foreach ($userLists as $list) {
            $listId = is_array($list) ? $list['id'] : $list->id;
            $counts['lists'][$listId] = (clone $base)->where('list_id', $listId)->count();
        }

        return $counts;
    }

    public static function getStatistics($userId)
    {
        $base = static::query()->forUser($userId);

        $total = (clone $base)->count();
        $completed = (clone $base)->where('completed', true)->count();

        return [
            'total'           => $total,
            'completed'       => $completed,
            'incomplete'      => $total - $completed,
            'important'       => (clone $base)->where('is_important', true)->count(),
            'overdue'         => (clone $base)->whereNotNull('due_date')
                ->whereDate('due_date', '<', today())
                ->where('completed', false)->count(),
            'due_today'       => (clone $base)->whereDate('due_date', today())->count(),
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }
}
