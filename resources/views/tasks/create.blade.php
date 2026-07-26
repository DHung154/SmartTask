@extends('layouts.app')

@php
$title = "Th\u{00EA}m c\u{00F4}ng vi\u{1EC7}c";
$selectedList = old('list_id', $preSelectedListId ?? '');
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
                    <label for="attachment">File đính kèm</label>
                    <input type="file" id="attachment" name="attachment"
                           class="form-control {{ $errors->has('attachment') ? 'is-invalid' : '' }}">
                    @error('attachment') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
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
                    <label for="list_id"><i class="fa-solid fa-layer-group"></i> Thuộc danh sách</label>
                    <select name="list_id" id="list_id" class="form-control {{ $errors->has('list_id') ? 'is-invalid' : '' }}">
                        <option value="" {{ (!is_numeric($selectedList)) ? 'selected' : '' }}>Công việc (mặc định)</option>
                        @foreach ($userLists ?? [] as $list)
                            <option value="{{ (int)$list['id'] }}" {{ ($selectedList != '' && $selectedList == $list['id']) ? 'selected' : '' }}>
                                {{ $list['name'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('list_id') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="progress">Tiến độ: <strong id="progressValue">{{ (int)old('progress', 0) }}%</strong></label>
                <input type="range" id="progress" name="progress" min="0" max="100" step="10" value="{{ (int)old('progress', 0) }}" oninput="document.getElementById('progressValue').textContent=this.value+'%'">
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

@endsection
