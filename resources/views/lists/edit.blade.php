@extends('layouts.app')

@php
$title = $title ?? "S\u{1EED}a danh s\u{00E1}ch";
$currentName = old('name', $list['name']);
@endphp

@section('content')
<div class="form-wrapper">
    <div class="content-header"><div class="header-title"><h1><i class="fa-solid fa-pen-to-square"></i> Sửa danh sách</h1></div><a href="/tasks?list={{ (int)$list['id'] }}" class="btn-text"><i class="fa-solid fa-arrow-left"></i> Quay lại</a></div>
    <div class="form-card"><form method="POST" action="/lists/edit?id={{ (int)$list['id'] }}" novalidate>
        @csrf
        <div class="form-group"><label for="name">Tên danh sách <span class="required">*</span></label><input type="text" id="name" name="name" maxlength="100" autofocus class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ $currentName }}">@error('name') <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span> @enderror</div>
        <div class="form-actions-right"><a href="/tasks?list={{ (int)$list['id'] }}" class="btn btn-secondary">Hủy</a><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi</button></div>
    </form></div>
    <div class="form-danger-zone"><form method="POST" action="/lists/delete" class="inline-form" onsubmit="return confirm('Xoa danh sach nay? Cac cong viec ben trong se duoc chuyen ve muc Cong viec.');">
        @csrf<input type="hidden" name="id" value="{{ (int)$list['id'] }}">
        <button type="submit" class="btn-text text-danger"><i class="fa-solid fa-trash"></i> Xóa danh sách này</button>
    </form></div>
</div>
@endsection
