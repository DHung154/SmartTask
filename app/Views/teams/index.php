<?php 
use App\Core\View;
use App\Core\Csrf;

ob_start(); 
?>

<div class="py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 m-0 text-white">Quản lý Nhóm Workspaces</h2>
        <a href="/teams/create" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Tạo Nhóm Mới
        </a>
    </div>

    <div class="row g-3">
        <?php if (empty($teams)): ?>
            <div class="col-12">
                <div class="p-4 text-center rounded bg-dark border border-secondary text-white-50">
                    <i class="fa-solid fa-users-slash fa-2x mb-2"></i>
                    <p class="m-0">Bạn chưa tham gia nhóm nào.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($teams as $team): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 bg-dark text-white border-secondary shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-info mb-2">
                                <i class="fa-solid fa-users me-2"></i><?= View::e($team['name']) ?>
                            </h5>
                            <p class="card-text text-white-50 small flex-grow-1">
                                <?= View::e($team['description'] ?? 'Chưa có mô tả.') ?>
                            </p>
                            <hr class="border-secondary my-2">
                            <div class="small text-white-50 mb-3">
                                <div><i class="fa-solid fa-user-shield me-1"></i> Chủ nhóm: <strong class="text-white"><?= View::e($team['owner_name'] ?? 'N/A') ?></strong></div>
                                <div><i class="fa-solid fa-user-group me-1"></i> Thành viên: <span class="badge bg-secondary"><?= (int)($team['total_members'] ?? 1) ?></span></div>
                            </div>
                            
                            <div class="d-flex gap-2 mt-auto">
                                <a href="/teams/detail?id=<?= (int)$team['id'] ?>" class="btn btn-outline-light btn-sm flex-grow-1">
                                    Xem chi tiết
                                </a>

                                <!-- Nút Xóa thể hiện chữ rõ ràng và style màu đỏ -->
                                <?php if (($team['user_role'] ?? 'owner') === 'owner'): ?>
                                    <form action="/teams/delete" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhóm này? Dữ liệu công việc liên quan sẽ bị xóa hoàn toàn.');">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$team['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm text-white px-3">
                                            Xóa
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require_once __DIR__ . '/../layout.php';
?>