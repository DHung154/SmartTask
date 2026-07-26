@extends('layouts.app')

@section('content')

<div class="form-wrapper">
    <div class="form-card">
        <a href="/teams/detail?id={{ (int)$team->id }}" class="btn btn-secondary btn-sm mb-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Quay lại nhóm
        </a>

        <h2 class="section-title">
            <i class="fa-solid fa-pen-to-square me-2"></i>Sửa thông tin nhóm
        </h2>

        <form method="POST" action="/teams/edit">
            @csrf
            <input type="hidden" name="id" value="{{ (int)$team->id }}">

            <div class="form-group">
                <label for="name">Tên nhóm <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                       value="{{ old('name', $team->name) }}" maxlength="100" required>
                @error('name')
                    <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Mô tả nhóm</label>
                <textarea id="description" name="description" class="form-control" rows="3"
                          placeholder="Mô tả ngắn về mục đích của nhóm...">{{ old('description', $team->description) }}</textarea>
            </div>

            <div class="form-actions-right">
                <a href="/teams/detail?id={{ (int)$team->id }}" class="btn btn-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check me-1"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
