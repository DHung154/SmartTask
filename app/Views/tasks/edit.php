<?php
use App\Core\Csrf;
use App\Core\View;

$title = "Sửa công việc";
$old = $old ?? [];
$errors = $errors ?? [];

$value = function ($key) use ($old, $task) {
    return array_key_exists($key, $old) ? $old[$key] : ($task[$key] ?? '');
};

$isImportant = !empty($old)
    ? !empty($old['is_important'])
    : !empty($task['is_important']);

ob_start();
?>

<div class="form-wrapper">
    <div class="content-header">
        <div class="header-title">
            <h1><i class="fa-solid fa-pen-to-square"></i> Sửa công việc</h1>
        </div>
        <a href="/" class="btn-text"><i class="fa-solid fa-arrow-left"></i> Về danh sách</a>
    </div>

    <div class="form-card">
        <form method="POST" action="/tasks/edit?id=<?= (int)$task['id'] ?>" enctype="multipart/form-data" novalidate>
            <?= Csrf::field() ?>

            <div class="form-group">
                <label for="title">Tên công việc <span class="required">*</span></label>
                <input type="text" id="title" name="title" maxlength="200"
                       class="form-control <?= View::invalid($errors, 'title') ?>"
                       value="<?= View::e($value('title')) ?>">
                <?= View::error($errors, 'title') ?>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="priority">Mức ưu tiên</label>
                    <?php $priority = $value('priority') ?: 'normal'; ?>
                    <select name="priority" id="priority" class="form-control <?= View::invalid($errors, 'priority') ?>">
                        <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Thấp</option>
                        <option value="normal" <?= $priority === 'normal' ? 'selected' : '' ?>>Bình thường</option>
                        <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>Cao</option>
                    </select>
                    <?= View::error($errors, 'priority') ?>
                </div>

                <div class="form-group half-width">
                    <label for="attachment">File đính kèm</label>
                    <input type="file" id="attachment" name="attachment"
                           class="form-control <?= View::invalid($errors, 'attachment') ?>">
                    <?= View::error($errors, 'attachment') ?>
                    <?php if (!empty($task['attachment_path'])): ?>
                        <div class="attachment-current">
                            <a href="<?= View::e($task['attachment_path']) ?>" target="_blank" rel="noopener">
                                <i class="fa-solid fa-paperclip"></i> <?= View::e($task['attachment_name'] ?: 'Xem file') ?>
                            </a>
                            <label><input type="checkbox" name="remove_attachment" value="1"> Gỡ file</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Mô tả</label>
                <textarea id="description" name="description" rows="4"
                          class="form-control <?= View::invalid($errors, 'description') ?>"><?= View::e($value('description')) ?></textarea>
                <?= View::error($errors, 'description') ?>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="due_date">Hạn chót</label>
                    <input type="date" id="due_date" name="due_date"
                           class="form-control <?= View::invalid($errors, 'due_date') ?>"
                           value="<?= View::e($value('due_date')) ?>">
                    <?= View::error($errors, 'due_date') ?>
                </div>

                <div class="form-group half-width">
                    <label for="team_id"><i class="fa-solid fa-users"></i> Phân vào Nhóm / Danh sách</label>
                    <?php 
                        $selectedTeam = $value('team_id'); 
                        $selectedList = $value('list_id');
                    ?>
                    <select name="team_id" id="team_id" class="form-control <?= View::invalid($errors, 'team_id') ?>">
                        <option value="" <?= empty($selectedTeam) ? 'selected' : '' ?>>-- Cá nhân (Mặc định) --</option>
                        
                        <?php if (!empty($teams)): ?>
                            <optgroup label="Nhóm Workspaces">
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= (int)$team['id'] ?>" <?= ($selectedTeam == $team['id']) ? 'selected' : '' ?>>
                                        <?= View::e($team['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>

                        <?php if (!empty($userLists)): ?>
                            <optgroup label="Danh sách cá nhân">
                                <?php foreach ($userLists as $list): ?>
                                    <option value="list_<?= (int)$list['id'] ?>" <?= (empty($selectedTeam) && $selectedList == $list['id']) ? 'selected' : '' ?>>
                                        <?= View::e($list['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                    <?= View::error($errors, 'team_id') ?>
                </div>
            </div>

            <div class="form-group">
                <?php $progress = (int)($value('progress') ?? 0); ?>
                <label for="progress">Tiến độ: <strong id="progressValue"><?= $progress ?>%</strong></label>
                <input type="range" id="progress" name="progress" min="0" max="100" step="10" value="<?= $progress ?>" oninput="document.getElementById('progressValue').textContent=this.value+'%'">
            </div>

            <div class="form-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="is_important" name="is_important" value="1"
                        <?= $isImportant ? 'checked' : '' ?>>
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
              onsubmit="return confirm('Chuyển công việc này vào thùng rác?');">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
            <input type="hidden" name="redirect" value="/">
            <button type="submit" class="btn-text text-danger">
                <i class="fa-solid fa-trash"></i> Chuyển vào thùng rác
            </button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>