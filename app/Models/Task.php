<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::updated(function (Task $task) {
            if (($task->completed || $task->status === 'done') && $task->repeat !== 'none') {
                $task->spawnNextOccurrence();
            }
        });
    }

    const PER_PAGE = 10;

    /** Các trạng thái Kanban theo thứ tự cột. */
    const STATUSES = ['todo', 'doing', 'review', 'done'];

    /** Các kiểu lặp lại được phép. */
    const REPEATS = ['none', 'daily', 'weekly', 'monthly'];

    protected $fillable = [
        'user_id', 'assignee_id', 'list_id', 'team_id', 'title', 'description',
        'attachment_path', 'attachment_name', 'completed', 'status', 'is_important',
        'priority', 'progress', 'due_date', 'repeat', 'repeat_until', 'repeat_parent_id',
        'reminder_sent_at', 'reminder_queued_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'is_important' => 'boolean',
        'progress' => 'integer',
        'due_date' => 'date',
        'repeat_until' => 'date',
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

    /** Người được giao việc (khác user là người tạo). */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function todoList()
    {
        return $this->belongsTo(TodoList::class, 'list_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function subtasks()
    {
        return $this->hasMany(Subtask::class)->orderBy('position')->orderBy('id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at');
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class)->orderBy('id');
    }

    // Query Scopes

    /**
     * Việc "của tôi" gồm cả việc tôi tạo lẫn việc người khác giao cho tôi.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('assignee_id', $userId);
        });
    }

    public function scopeApplyFilter($query, $filter)
    {
        switch ($filter) {
            case 'important':
                return $query->where('is_important', true);
            case 'assigned-to-me':
                return $query->where('assignee_id', auth()->id());
            case 'assigned-by-me':
                return $query->where('user_id', auth()->id())
                    ->whereNotNull('assignee_id')
                    ->where('assignee_id', '!=', auth()->id());
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

    // ---------------------------------------------------------------
    // Trạng thái / tiến độ
    //
    // Quy ước để 3 cột completed - status - progress không đá nhau:
    //   * status là nguồn sự thật cho cột Kanban.
    //   * completed = true khi và chỉ khi status = 'done'.
    //   * progress: nếu task có việc con thì tính theo tỉ lệ việc con đã xong,
    //     ngược lại do người dùng tự đặt.
    // ---------------------------------------------------------------

    public static function normalizeStatus($status): string
    {
        return in_array($status, self::STATUSES, true) ? $status : 'todo';
    }

    public static function statusLabels(): array
    {
        return [
            'todo'   => __('kanban.todo'),
            'doing'  => __('kanban.doing'),
            'review' => __('kanban.review'),
            'done'   => __('kanban.done'),
        ];
    }

    /**
     * Đặt trạng thái và đồng bộ completed/progress theo đúng quy ước trên.
     */
    public function applyStatus(string $status): self
    {
        $status = self::normalizeStatus($status);
        $attributes = ['status' => $status, 'completed' => $status === 'done'];

        if (!$this->subtasks()->exists()) {
            if ($status === 'done') {
                $attributes['progress'] = 100;
            } elseif ($status === 'todo') {
                $attributes['progress'] = 0;
            } elseif ((int) $this->progress >= 100 || (int) $this->progress <= 0) {
                // Rời khỏi hai đầu mút thì cho một mốc giữa hợp lý.
                $attributes['progress'] = $status === 'review' ? 75 : 50;
            }
        }

        $this->update($attributes);

        return $this;
    }

    /**
     * Tính lại tiến độ từ việc con. Xong hết thì tự chuyển sang 'done',
     * bỏ tick một việc thì kéo ngược về 'doing'.
     */
    public function refreshFromSubtasks(): self
    {
        $total = $this->subtasks()->count();

        if ($total === 0) {
            return $this;
        }

        $done = $this->subtasks()->where('completed', true)->count();
        $progress = (int) round($done * 100 / $total);

        $status = $this->status;
        if ($done === $total) {
            $status = 'done';
        } elseif ($this->status === 'done') {
            $status = 'doing';
        } elseif ($this->status === 'todo' && $done > 0) {
            $status = 'doing';
        }

        $this->update([
            'progress'  => $progress,
            'status'    => $status,
            'completed' => $status === 'done',
        ]);

        return $this;
    }

    public function subtaskSummary(): array
    {
        $subtasks = $this->relationLoaded('subtasks') ? $this->subtasks : $this->subtasks()->get();

        return [
            'total' => $subtasks->count(),
            'done'  => $subtasks->where('completed', true)->count(),
        ];
    }

    // ---------------------------------------------------------------
    // Lặp lại định kỳ
    // ---------------------------------------------------------------

    public static function repeatLabels(): array
    {
        return [
            'none'    => __('repeat.none'),
            'daily'   => __('repeat.daily'),
            'weekly'  => __('repeat.weekly'),
            'monthly' => __('repeat.monthly'),
        ];
    }

    public static function normalizeRepeat($repeat): string
    {
        return in_array($repeat, self::REPEATS, true) ? $repeat : 'none';
    }

    /** Hạn kế tiếp theo chu kỳ lặp, null nếu không lặp hoặc đã quá repeat_until. */
    public function nextDueDate(): ?\Illuminate\Support\Carbon
    {
        if ($this->repeat === 'none') {
            return null;
        }

        $baseDate = $this->due_date ? $this->due_date->copy() : now();

        $next = match ($this->repeat) {
            'daily'   => $baseDate->addDay(),
            'weekly'  => $baseDate->addWeek(),
            'monthly' => $baseDate->addMonth(),
            default   => null,
        };

        if (!$next || ($this->repeat_until && $next->gt($this->repeat_until))) {
            return null;
        }

        return $next;
    }

    /**
     * Sinh lần kế tiếp của một việc lặp lại. Trả về null nếu không đến lượt
     * hoặc lần kế tiếp đã tồn tại (tránh tạo trùng khi chạy lại command).
     */
    public function spawnNextOccurrence(): ?self
    {
        $next = $this->nextDueDate();
        if (!$next) {
            return null;
        }

        $rootId = $this->repeat_parent_id ?: $this->id;

        $exists = static::withTrashed()
            ->where(function ($q) use ($rootId) {
                $q->where('repeat_parent_id', $rootId)->orWhere('id', $rootId);
            })
            ->whereDate('due_date', $next->toDateString())
            ->exists();

        if ($exists) {
            return null;
        }

        $clone = static::create([
            'user_id'          => $this->user_id,
            'assignee_id'      => $this->assignee_id,
            'list_id'          => $this->list_id,
            'team_id'          => $this->team_id,
            'title'            => $this->title,
            'description'      => $this->description,
            'completed'        => false,
            'status'           => 'todo',
            'is_important'     => $this->is_important,
            'priority'         => $this->priority,
            'progress'         => 0,
            'due_date'         => $next->toDateString(),
            'repeat'           => $this->repeat,
            'repeat_until'     => $this->repeat_until,
            'repeat_parent_id' => $rootId,
        ]);

        // Chép khung việc con nhưng để trống trạng thái hoàn thành.
        foreach ($this->subtasks()->get() as $subtask) {
            Subtask::create([
                'task_id'   => $clone->id,
                'title'     => $subtask->title,
                'completed' => false,
                'position'  => $subtask->position,
            ]);
        }

        return $clone;
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

        return $query->with(['assignee:id,name', 'subtasks:id,task_id,completed'])
            ->withCount('comments', 'attachments')
            ->applySort($sort)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public static function getTaskCounts($userId, $userLists = [])
    {
        $base = static::query()->forUser($userId);

        $counts = [
            'inbox'            => (clone $base)->applyFilter('inbox')->count(),
            'my-day'           => (clone $base)->applyFilter('my-day')->count(),
            'important'        => (clone $base)->applyFilter('important')->count(),
            'planned'          => (clone $base)->applyFilter('planned')->count(),
            'overdue'          => (clone $base)->applyFilter('overdue')->count(),
            'completed'        => (clone $base)->applyFilter('completed')->count(),
            'incomplete'       => (clone $base)->applyFilter('incomplete')->count(),
            'assigned-to-me'   => (clone $base)->where('assignee_id', $userId)->count(),
            'assigned-by-me'   => static::query()->where('user_id', $userId)
                ->whereNotNull('assignee_id')
                ->where('assignee_id', '!=', $userId)->count(),
            'trash'            => static::onlyTrashed()->forUser($userId)->count(),
            'lists'            => [],
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
