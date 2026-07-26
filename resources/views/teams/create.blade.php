@extends('layouts.app')

@section('content')

<div class="py-3">
    <div class="mb-4">
        <a href="/teams" class="btn btn-outline-secondary btn-sm text-white mb-2">
            <i class="fa-solid fa-arrow-left me-1"></i> Quay lại danh sách
        </a>
        <h2 class="h3 m-0 text-white">Tạo Nhóm Workspace Mới</h2>
    </div>

    <div class="row">
        <div class="col-md-8 col-lg-6">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body p-4">
                    <form action="/teams/store" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">Tên nhóm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-dark text-white border-secondary"
                                   id="name" name="name" placeholder="Nhập tên nhóm..." required>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Mô tả nhóm</label>
                            <textarea class="form-control bg-dark text-white border-secondary"
                                      id="description" name="description" rows="3"
                                      placeholder="Mô tả ngắn về mục đích của nhóm..."></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="/teams" class="btn btn-secondary">Hủy</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-plus me-1"></i> Tạo nhóm
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
