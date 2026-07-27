@extends('layouts.app')

@php
$title = 'Sửa công việc';

$value = function ($key) use ($task) {
    return old($key, $task[$key] ?? '');
};

$isImportant = old('is_important', !empty($task->is_important));
$members = $members ?? collect();
$teamMembers = $teamMembers ?? [];
$statusLabels = App\Models\Task::statusLabels();
$repeatLabels = App\Models\Task::repeatLabels();

// due_date/repeat_until là Carbon, phải đổi sang Y-m-d cho input type=date.
$dueDate = old('due_date', optional($task->due_date)->toDateString());
$repeatUntil = old('repeat_until', optional($task->repeat_until)->toDateString());
$currentStatus = old('status', $task->status ?? 'todo');
$currentRepeat = old('repeat', $task->repeat ?? 'none');
$currentAssignee = old('assignee_id', $task->assignee_id);

$subtaskSummary = $task->subtaskSummary();
$hasSubtasks = $subtaskSummary['total'] > 0;
@endphp

@section('content')

<div class="form-wrapper">
    <div class="content-header">
        <div class="header-title">
            <h1><i class="fa-solid fa-pen-to-square"></i> Sửa công việc</h1>
        </div>
        <a href="/" class="btn-text"><i class="fa-solid fa-arrow-left"></i> Về danh sách</a>
    </div>

    <div class="form-card">
        <form method="POST" action="/tasks/edit?id={{ (int)$task->id }}" enctype="multipart/form-data" novalidate>
            @csrf
            <input type="hidden" name="id" value="{{ (int)$task->id }}">

            <div class="form-group">
                <label for="title">Tên công việc <span class="required">*</span></label>
                <input type="text" id="title" name="title" maxlength="200"
                       class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
                       value="{{ $value('title') }}">
                @error('title') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="priority">Mức ưu tiên</label>
                    @php $priority = $value('priority') ?: 'normal'; @endphp
                    <select name="priority" id="priority" class="form-control {{ $errors->has('priority') ? 'is-invalid' : '' }}">
                        <option value="low" {{ $priority === 'low' ? 'selected' : '' }}>Thấp</option>
                        <option value="normal" {{ $priority === 'normal' ? 'selected' : '' }}>Bình thường</option>
                        <option value="high" {{ $priority === 'high' ? 'selected' : '' }}>Cao</option>
                    </select>
                    @error('priority') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                </div>

                <div class="form-group half-width">
                    <label for="status"><i class="fa-solid fa-table-columns"></i> Trạng thái</label>
                    <select name="status" id="status" class="form-control">
                        @foreach ($statusLabels as $key => $label)
                            <option value="{{ $key }}" {{ $currentStatus === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Mô tả</label>
                <textarea id="description" name="description" rows="4"
                          class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}">{{ $value('description') }}</textarea>
                @error('description') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="due_date">Hạn chót</label>
                    <input type="date" id="due_date" name="due_date"
                           class="form-control {{ $errors->has('due_date') ? 'is-invalid' : '' }}"
                           value="{{ $dueDate }}">
                    @error('due_date') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                </div>

                <div class="form-group half-width">
                    <label for="team_id"><i class="fa-solid fa-users"></i> Phân vào Nhóm / Danh sách</label>
                    @php
                        $selectedTeam = $value('team_id');
                        $selectedList = $value('list_id');
                    @endphp
                    <select name="team_id" id="team_id" class="form-control {{ $errors->has('team_id') ? 'is-invalid' : '' }}"
                            data-assignee-target="#assignee_id">
                        <option value="" {{ empty($selectedTeam) ? 'selected' : '' }}>-- Cá nhân (Mặc định) --</option>

                        @if (!empty($teams))
                            <optgroup label="Nhóm Workspaces">
                                @foreach ($teams as $team)
                                    <option value="{{ (int)$team['id'] }}" {{ ($selectedTeam == $team['id']) ? 'selected' : '' }}>
                                        {{ $team['name'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif

                        @if (!empty($userLists))
                            <optgroup label="Danh sách cá nhân">
                                @foreach ($userLists as $list)
                                    <option value="list_{{ (int)$list['id'] }}" {{ (empty($selectedTeam) && $selectedList == $list['id']) ? 'selected' : '' }}>
                                        {{ $list['name'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    @error('team_id') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="assignee_id"><i class="fa-solid fa-user-check"></i> Giao cho</label>
                    <select name="assignee_id" id="assignee_id"
                            class="form-control {{ $errors->has('assignee_id') ? 'is-invalid' : '' }}">
                        <option value="">-- Chưa giao cho ai --</option>
                        @foreach ($members as $member)
                            <option value="{{ (int)$member->id }}" {{ (int)$currentAssignee === (int)$member->id ? 'selected' : '' }}>
                                {{ $member->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assignee_id') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                    <small class="form-hint">Chỉ giao được cho thành viên của nhóm đã chọn ở trên.</small>
                </div>

                <div class="form-group half-width">
                    <label for="repeat"><i class="fa-solid fa-rotate"></i> Lặp lại</label>
                    <select name="repeat" id="repeat" class="form-control {{ $errors->has('repeat') ? 'is-invalid' : '' }}"
                            data-repeat-toggle>
                        @foreach ($repeatLabels as $key => $label)
                            <option value="{{ $key }}" {{ $currentRepeat === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('repeat') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror

                    <div data-repeat-until-wrapper style="{{ $currentRepeat === 'none' ? 'display:none;' : '' }} margin-top: 8px;">
                        <label for="repeat_until">Lặp đến ngày</label>
                        <input type="date" id="repeat_until" name="repeat_until"
                               class="form-control {{ $errors->has('repeat_until') ? 'is-invalid' : '' }}"
                               value="{{ $repeatUntil }}">
                        @error('repeat_until') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                @php $progress = (int) old('progress', $task->progress ?? 0); @endphp
                <label for="progress">Tiến độ: <strong id="progressValue">{{ $progress }}%</strong></label>
                <input type="range" id="progress" name="progress" min="0" max="100" step="10"
                       value="{{ $progress }}" {{ $hasSubtasks ? 'disabled' : '' }}
                       oninput="document.getElementById('progressValue').textContent=this.value+'%'">
                @if ($hasSubtasks)
                    <small class="form-hint">
                        <i class="fa-solid fa-circle-info"></i>
                        Tiến độ đang tính tự động theo việc con ({{ $subtaskSummary['done'] }}/{{ $subtaskSummary['total'] }} đã xong).
                    </small>
                @endif
            </div>

            <div class="form-group">
                <label for="attachments"><i class="fa-solid fa-paperclip"></i> Thêm file đính kèm</label>
                <input type="file" id="attachments" name="attachments[]" multiple
                       class="form-control {{ $errors->has('attachment') ? 'is-invalid' : '' }}">
                @error('attachment') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                <small class="form-hint">Tối đa 5 file, mỗi file 5MB.</small>
            </div>

            <div class="form-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="is_important" name="is_important" value="1"
                        {{ $isImportant ? 'checked' : '' }}>
                    <label for="is_important">Đánh dấu quan trọng <i class="fa-solid fa-star text-warning"></i></label>
                </div>
            </div>

            <div class="form-actions-right">
                <a href="/" class="btn btn-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>

    {{-- Danh sách file đã đính kèm; form xoá phải nằm ngoài form chính --}}
    @if ($task->attachments->isNotEmpty())
        <div class="form-card">
            <h2 class="section-title"><i class="fa-solid fa-paperclip me-2"></i>File đính kèm ({{ $task->attachments->count() }})</h2>

            <div class="attachment-list">
                @foreach ($task->attachments as $attachment)
                    <div class="attachment-row">
                        <a href="{{ $attachment->path }}" target="_blank" rel="noopener" class="attachment-link">
                            <i class="fa-solid {{ $attachment->isImage() ? 'fa-image' : 'fa-file' }}"></i>
                            <span class="attachment-name">{{ $attachment->name }}</span>
                            @if ($attachment->humanSize())
                                <span class="attachment-size">{{ $attachment->humanSize() }}</span>
                            @endif
                        </a>
                        <form method="POST" action="/tasks/attachment/remove"
                              onsubmit="return confirm('Gỡ file này? File sẽ bị xoá khỏi máy chủ.');">
                            @csrf
                            <input type="hidden" name="attachment_id" value="{{ (int)$attachment->id }}">
                            <button type="submit" class="btn btn-danger btn-sm" title="Gỡ file">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Việc con --}}
    <div class="form-card" id="subtasks">
        <h2 class="section-title">
            <i class="fa-solid fa-list-check me-2"></i>Việc con
            @if ($hasSubtasks)
                <span class="priority-badge priority-normal">{{ $subtaskSummary['done'] }}/{{ $subtaskSummary['total'] }}</span>
            @endif
        </h2>

        @if ($hasSubtasks)
            @php $percent = (int) round($subtaskSummary['done'] * 100 / $subtaskSummary['total']); @endphp
            <div class="subtask-progress"><span style="width: {{ $percent }}%"></span></div>

            <div class="subtask-list">
                @foreach ($task->subtasks as $subtask)
                    <div class="subtask-row {{ $subtask->completed ? 'is-done' : '' }}">
                        <form method="POST" action="/subtasks/toggle" class="subtask-toggle-form">
                            @csrf
                            <input type="hidden" name="id" value="{{ (int)$subtask->id }}">
                            <button type="submit" class="subtask-check" title="{{ $subtask->completed ? 'Bỏ đánh dấu' : 'Đánh dấu xong' }}">
                                <i class="fa-{{ $subtask->completed ? 'solid fa-circle-check' : 'regular fa-circle' }}"></i>
                            </button>
                        </form>
                        <span class="subtask-title">{{ $subtask->title }}</span>
                        <form method="POST" action="/subtasks/delete" onsubmit="return confirm('Xoá việc con này?');">
                            @csrf
                            <input type="hidden" name="id" value="{{ (int)$subtask->id }}">
                            <button type="submit" class="btn-text text-danger" title="Xoá"><i class="fa-solid fa-xmark"></i></button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <p class="empty-hint">Chưa có việc con. Chia nhỏ công việc để dễ theo dõi hơn.</p>
        @endif

        <form method="POST" action="/subtasks/create" class="subtask-add-form">
            @csrf
            <input type="hidden" name="task_id" value="{{ (int)$task->id }}">
            <input type="text" name="title" maxlength="200" class="form-control" placeholder="Thêm việc con rồi nhấn Enter" required>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i></button>
        </form>
    </div>

    {{-- Bình luận --}}
    <div class="form-card" id="comments">
        <h2 class="section-title">
            <i class="fa-solid fa-comments me-2"></i>Bình luận ({{ $task->comments->count() }})
        </h2>

        @if ($task->comments->isEmpty())
            <p class="empty-hint">Chưa có bình luận nào.</p>
        @else
            <div class="comment-list">
                @foreach ($task->comments as $comment)
                    <div class="comment-row">
                        <span class="avatar comment-avatar">{{ mb_strtoupper(mb_substr($comment->user->name ?? 'U', 0, 1)) }}</span>
                        <div class="comment-body">
                            <div class="comment-head">
                                <strong>{{ $comment->user->name ?? 'Người dùng' }}</strong>
                                <span class="comment-time">{{ $comment->created_at?->diffForHumans() }}</span>
                                @if ((int)$comment->user_id === auth()->id() || (int)$task->user_id === auth()->id())
                                    <form method="POST" action="/comments/delete" onsubmit="return confirm('Xoá bình luận này?');">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ (int)$comment->id }}">
                                        <button type="submit" class="btn-text text-danger comment-delete" title="Xoá"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                @endif
                            </div>
                            <p class="comment-text">{{ $comment->body }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="/comments/create" class="comment-add-form">
            @csrf
            <input type="hidden" name="task_id" value="{{ (int)$task->id }}">
            <textarea name="body" rows="2" maxlength="2000" class="form-control" placeholder="Viết bình luận..." required></textarea>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Gửi</button>
        </form>
    </div>

    <div class="form-danger-zone">
        <form method="POST" action="/tasks/delete" class="inline-form"
              onsubmit="return confirm('Chuyển công việc này vào thùng rác?');">
            @csrf
            <input type="hidden" name="id" value="{{ (int)$task->id }}">
            <input type="hidden" name="redirect" value="/">
            <button type="submit" class="btn-text text-danger">
                <i class="fa-solid fa-trash"></i> Chuyển vào thùng rác
            </button>
        </form>
    </div>
</div>

<script type="application/json" id="team-members-map">@json($teamMembers)</script>
<script src="/js/task-form.js?v=20260728a"></script>

@endsection
