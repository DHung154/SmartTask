@extends('layouts.app')

@php
$title = "S\u{1EED}a c\u{00F4}ng vi\u{1EC7}c";

$value = function ($key) use ($task) {
    return old($key, $task[$key] ?? '');
};

$isImportant = old('is_important', !empty($task->is_important));
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
                    <label for="attachment">File đính kèm</label>
                    <input type="file" id="attachment" name="attachment"
                           class="form-control {{ $errors->has('attachment') ? 'is-invalid' : '' }}">
                    @error('attachment') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                    @if (!empty($task->attachment_path))
                        <div class="attachment-current">
                            <a href="{{ $task->attachment_path }}" target="_blank" rel="noopener">
                                <i class="fa-solid fa-paperclip"></i> {{ $task->attachment_name ?: 'Xem file' }}
                            </a>
                            <label><input type="checkbox" name="remove_attachment" value="1"> Gỡ file</label>
                        </div>
                    @endif
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
                           value="{{ $value('due_date') }}">
                    @error('due_date') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                </div>

                <div class="form-group half-width">
                    <label for="team_id"><i class="fa-solid fa-users"></i> Phân vào Nhóm / Danh sách</label>
                    @php
                        $selectedTeam = $value('team_id');
                        $selectedList = $value('list_id');
                    @endphp
                    <select name="team_id" id="team_id" class="form-control {{ $errors->has('team_id') ? 'is-invalid' : '' }}">
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

            <div class="form-group">
                @php $progress = (int)($value('progress') ?? 0); @endphp
                <label for="progress">Tiến độ: <strong id="progressValue">{{ $progress }}%</strong></label>
                <input type="range" id="progress" name="progress" min="0" max="100" step="10" value="{{ $progress }}" oninput="document.getElementById('progressValue').textContent=this.value+'%'">
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

    <div class="form-danger-zone">
        <form method="POST" action="/tasks/delete" class="inline-form"
              onsubmit="return confirm('Chuy\u{1EC3}n c\u{00F4}ng vi\u{1EC7}c n\u{00E0}y v\u{00E0}o th\u{00F9}ng r\u{00E1}c?');">
            @csrf
            <input type="hidden" name="id" value="{{ (int)$task->id }}">
            <input type="hidden" name="redirect" value="/">
            <button type="submit" class="btn-text text-danger">
                <i class="fa-solid fa-trash"></i> Chuyển vào thùng rác
            </button>
        </form>
    </div>
</div>

@endsection
