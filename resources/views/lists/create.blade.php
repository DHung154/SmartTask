@extends('layouts.app')

@php
$title = $title ?? "Danh s\u{00E1}ch m\u{1EDB}i";
@endphp

@section('content')
<div class="form-wrapper">
    <div class="content-header"><div class="header-title"><h1><i class="fa-solid fa-folder-plus"></i> Tạo danh sách mới</h1></div><a href="/" class="btn-text"><i class="fa-solid fa-arrow-left"></i> Quay lại</a></div>
    <div class="form-card"><form method="POST" action="/lists/create" novalidate>
        @csrf
        <div class="form-group"><label for="name">Tên danh sách <span class="required">*</span></label><input type="text" id="name" name="name" maxlength="100" autofocus class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" placeholder="VD: Đi chợ, Công việc, Học tập..." value="{{ old('name') }}">@error('name') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror<small class="form-hint">Nhóm các công việc liên quan vào cùng một danh sách.</small></div>
        <div class="form-actions-right"><a href="/" class="btn btn-secondary">Hủy</a><button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Tạo danh sách</button></div>
    </form></div>
</div>
@endsection
