<?php
use App\Core\View;
use App\Core\Csrf;

/** 
 * @var array $team
 * @var string $myRole
 * @var array $members
 * @var array $tasks
 */
$team = $team ?? [];
$myRole = $myRole ?? 'member';
$members = $members ?? [];
$tasks = $tasks ?? [];

ob_start();
?>

<div class="container-fluid py-4 px-4" style="color: #fff;">
    <!-- Header trang nhóm -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="/teams" class="btn btn-outline-light btn-sm mb-3 rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Quay lại danh sách
            </a>
            <div class="p-4 rounded-4 shadow-sm" style="background-color: #212529; border: 1px solid #373b3e;">
                <h1 class="h2 fw-bold text-white mb-2"><?= View::e($team['name'] ?? '') ?></h1>
                <p class="text-white-50 mb-0"><?= View::e($team['description'] ?? 'Chưa có mô tả cho nhóm này.') ?></p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Cột trái: Danh sách công việc -->
        <div class="col-lg-8">
            <div class="rounded-4 shadow-sm h-100 overflow-hidden" style="background-color: #212529; border: 1px solid #373b3e;">
                <div class="py-3 px-4 d-flex justify-content-between align-items-center border-bottom" style="border-color: #373b3e !important;">
                    <h5 class="card-title mb-0 fw-bold text-white">
                        <i class="fa-solid fa-list-check me-2 text-primary"></i>Danh sách công việc
                    </h5>
                    <span class="badge bg-secondary px-3 py-2 rounded-pill"><?= count($tasks) ?> công việc</span>
                </div>
                <div class="p-4">
                    <?php if (!empty($tasks)): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($tasks as $task): ?>
                                <div class="p-3 rounded-3 d-flex justify-content-between align-items-center shadow-sm" style="background-color: #2c3034; border: 1px solid #373b3e;">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 text-primary fs-4">
                                            <i class="fa-solid fa-circle-check"></i>
                                        </div>
                                        <div>
                                            <!-- Nút bấm tên công việc -->
                                            <a href="/tasks/edit?id=<?= (int)$task['id'] ?>" class="btn btn-primary btn-sm fw-bold px-3 py-2 shadow-sm text-decoration-none mb-2 d-inline-flex align-items-center">
                                                <i class="fa-solid fa-pen-to-square me-2"></i><?= View::e($task['title']) ?>
                                            </a>
                                            <div class="text-white-50 small">
                                                <i class="fa-regular fa-user me-1"></i>Người tạo: <span class="text-white fw-semibold"><?= View::e($task['author_name']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge bg-secondary text-light px-3 py-2 fw-semibold border" style="border-color: #495057 !important;"><?= View::e($task['status'] ?? 'Cần làm') ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-white-50">
                            <i class="fa-solid fa-folder-open fa-3x mb-3 d-block"></i>
                            <p class="mb-0">Chưa có công việc nào dành riêng cho nhóm này.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cột phải: Mời thành viên & Danh sách thành viên -->
        <div class="col-lg-4 d-flex flex-column gap-4">
            <!-- Khối Mời thành viên -->
            <?php if (in_array($myRole, ['owner', 'admin'])): ?>
                <div class="rounded-4 shadow-sm overflow-hidden" style="background-color: #212529; border: 1px solid #373b3e;">
                    <div class="py-3 px-4 border-bottom" style="border-color: #373b3e !important;">
                        <h5 class="card-title mb-0 fw-bold text-white">
                            <i class="fa-solid fa-user-plus me-2 text-success"></i>Mời thành viên
                        </h5>
                    </div>
                    <div class="p-4">
                        <form action="/teams/add-member" method="POST">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="team_id" value="<?= (int)($team['id'] ?? 0) ?>">
                            
                            <div class="mb-3">
                                <label class="form-label small text-white-50 fw-semibold">Email người dùng</label>
                                <input type="email" name="email" class="form-control text-white py-2" style="background-color: #2c3034; border-color: #495057;" placeholder="nhap@email.com" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small text-white-50 fw-semibold">Vai trò</label>
                                <!-- Đã sửa màu chữ trắng và nền rõ ràng cho select -->
                                <select name="role" class="form-select text-white py-2" style="background-color: #2c3034; border-color: #495057; color: #ffffff !important;">
                                    <option value="member" style="background-color: #212529; color: #ffffff;">Member</option>
                                    <option value="admin" style="background-color: #212529; color: #ffffff;">Admin</option>
                                </select>
                            </div>

                            <!-- Đã sửa nút màu xanh lá nổi bật, chữ trắng rõ ràng -->
                            <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm text-white" style="background-color: #198754; border-color: #198754; color: #ffffff !important;">
                                <i class="fa-solid fa-paper-plane me-2"></i>Thêm vào nhóm
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Khối Danh sách thành viên -->
            <div class="rounded-4 shadow-sm overflow-hidden" style="background-color: #212529; border: 1px solid #373b3e;">
                <div class="py-3 px-4 border-bottom" style="border-color: #373b3e !important;">
                    <h5 class="card-title mb-0 fw-bold text-white">
                        <i class="fa-solid fa-users me-2 text-info"></i>Thành viên (<?= count($members) ?>)
                    </h5>
                </div>
                <div class="p-0">
                    <div class="d-flex flex-column">
                        <?php foreach ($members as $member): ?>
                            <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom" style="border-color: #2c3034 !important;">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary text-white rounded-circle fw-bold d-flex align-items-center justify-content-center me-3 shadow-sm" style="width:40px; height:40px; min-width:40px;">
                                        <?= View::e(mb_strtoupper(mb_substr($member['name'] ?? 'U', 0, 1))) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-white"><?= View::e($member['name'] ?? '') ?></div>
                                        <div class="text-white-50" style="font-size: 0.75rem;"><?= View::e($member['email'] ?? '') ?></div>
                                    </div>
                                </div>
                                <span class="badge bg-<?= ($member['role'] ?? '') === 'owner' ? 'danger' : (($member['role'] ?? '') === 'admin' ? 'warning' : 'secondary') ?> px-2 py-1">
                                    <?= View::e(strtoupper($member['role'] ?? 'MEMBER')) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>