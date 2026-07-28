@extends('layouts.app')

@php
$title = 'Thêm công việc';
$members = $members ?? collect();
$teamMembers = $teamMembers ?? [];
$statusLabels = App\Models\Task::statusLabels();
$repeatLabels = App\Models\Task::repeatLabels();

// Giá trị select gộp nhóm + danh sách: "12" là nhóm, "list_3" là danh sách cá nhân.
$preList = $preSelectedListId ?? '';
$selectedCombo = old('team_id', is_numeric($preList) ? 'list_' . (int) $preList : '');
$currentRepeat = old('repeat', 'none');
@endphp

@section('content')

<div class="form-wrapper">
    <div class="content-header">
        <div class="header-title">
            <h1><i class="fa-solid fa-circle-plus"></i> Thêm công việc</h1>
        </div>
        <a href="/" class="btn-text"><i class="fa-solid fa-arrow-left"></i> Về danh sách</a>
    </div>

    <div class="form-card">
        <form method="POST" action="/tasks/create" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="form-group">
                <label for="title">Tên công việc <span class="required">*</span></label>
                <input type="text" id="title" name="title" maxlength="200" autofocus
                       class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
                       placeholder="Bạn cần làm gì?"
                       value="{{ old('title') }}">
                @error('title') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="priority">Mức ưu tiên</label>
                    @php $priority = old('priority', 'normal'); @endphp
                    <select name="priority" id="priority" class="form-control {{ $errors->has('priority') ? 'is-invalid' : '' }}">
                        <option value="low" {{ $priority === 'low' ? 'selected' : '' }}>Thấp</option>
                        <option value="normal" {{ $priority === 'normal' ? 'selected' : '' }}>Bình thường</option>
                        <option value="high" {{ $priority === 'high' ? 'selected' : '' }}>Cao</option>
                    </select>
                    @error('priority') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                </div>

                <div class="form-group half-width">
                    <label for="status"><i class="fa-solid fa-table-columns"></i> Trạng thái</label>
                    @php $currentStatus = old('status', 'todo'); @endphp
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
                          class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}"
                          placeholder="Thêm chi tiết...">{{ old('description') }}</textarea>
                @error('description') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="due_date">Hạn chót (không bắt buộc)</label>
                    <input type="date" id="due_date" name="due_date"
                           class="form-control {{ $errors->has('due_date') ? 'is-invalid' : '' }}"
                           value="{{ old('due_date') }}">
                    @error('due_date') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                </div>

                <div class="form-group half-width">
                    <label for="team_id"><i class="fa-solid fa-layer-group"></i> Phân vào Nhóm / Danh sách</label>
                    <select name="team_id" id="team_id" class="form-control {{ $errors->has('list_id') ? 'is-invalid' : '' }}">
                        <option value="" {{ $selectedCombo === '' ? 'selected' : '' }}>-- Cá nhân (Mặc định) --</option>

                        @if (!empty($teams) && count($teams))
                            <optgroup label="Nhóm Workspaces">
                                @foreach ($teams as $team)
                                    <option value="{{ (int)$team['id'] }}" {{ $selectedCombo == $team['id'] ? 'selected' : '' }}>
                                        {{ $team['name'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif

                        @if (!empty($userLists) && count($userLists))
                            <optgroup label="Danh sách cá nhân">
                                @foreach ($userLists as $list)
                                    <option value="list_{{ (int)$list['id'] }}" {{ $selectedCombo === 'list_' . $list['id'] ? 'selected' : '' }}>
                                        {{ $list['name'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    @error('list_id') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="assignee_id"><i class="fa-solid fa-user-check"></i> Giao cho</label>
                    @php $currentAssignee = old('assignee_id'); @endphp
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
                               value="{{ old('repeat_until') }}">
                        @error('repeat_until') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="progress">Tiến độ: <strong id="progressValue">{{ (int)old('progress', 0) }}%</strong></label>
                <input type="range" id="progress" name="progress" min="0" max="100" step="10" value="{{ (int)old('progress', 0) }}" oninput="document.getElementById('progressValue').textContent=this.value+'%'">
            </div>

            <div class="form-group">
                <label for="attachments"><i class="fa-solid fa-paperclip"></i> File đính kèm</label>
                <input type="file" id="attachments" name="attachments[]" multiple
                       class="form-control {{ $errors->has('attachment') ? 'is-invalid' : '' }}">
                @error('attachment') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                <small class="form-hint">Tối đa 5 file, mỗi file 5MB.</small>
            </div>

            <div class="form-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="is_important" name="is_important" value="1"
                        {{ old('is_important') ? 'checked' : '' }}>
                    <label for="is_important">Đánh dấu quan trọng <i class="fa-solid fa-star text-warning"></i></label>
                </div>
            </div>

            <div class="form-actions-right">
                <a href="/" class="btn btn-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Thêm công việc
                </button>
            </div>
        </form>
    </div>
</div>

<script type="application/json" id="team-members-map">@json($teamMembers)</script>
<script type="application/json" id="user-team-roles">@json($userTeamRoles ?? [])</script>
<script src="/js/task-form.js?v=20260728c"></script>

@endsection
