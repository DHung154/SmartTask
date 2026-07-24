<?php
use App\Core\Csrf;
use App\Core\View;

$title = "S\u{1EED}a c\u{00F4}ng vi\u{1EC7}c";
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
            <h1><i class="fa-solid fa-pen-to-square"></i> S&#7917;a c&#244;ng vi&#7879;c</h1>
        </div>
        <a href="/" class="btn-text"><i class="fa-solid fa-arrow-left"></i> V&#7873; danh s&#225;ch</a>
    </div>

    <div class="form-card">
        <form method="POST" action="/tasks/edit?id=<?= (int)$task['id'] ?>" enctype="multipart/form-data" novalidate>
            <?= Csrf::field() ?>

            <div class="form-group">
                <label for="title">T&#234;n c&#244;ng vi&#7879;c <span class="required">*</span></label>
                <input type="text" id="title" name="title" maxlength="200"
                       class="form-control <?= View::invalid($errors, 'title') ?>"
                       value="<?= View::e($value('title')) ?>">
                <?= View::error($errors, 'title') ?>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="priority">M&#7913;c &#432;u ti&#234;n</label>
                    <?php $priority = $value('priority') ?: 'normal'; ?>
                    <select name="priority" id="priority" class="form-control <?= View::invalid($errors, 'priority') ?>">
                        <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Th&#7845;p</option>
                        <option value="normal" <?= $priority === 'normal' ? 'selected' : '' ?>>B&#236;nh th&#432;&#7901;ng</option>
                        <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>Cao</option>
                    </select>
                    <?= View::error($errors, 'priority') ?>
                </div>

                <div class="form-group half-width">
                    <label for="attachment">File &#273;&#237;nh k&#232;m</label>
                    <input type="file" id="attachment" name="attachment"
                           class="form-control <?= View::invalid($errors, 'attachment') ?>">
                    <?= View::error($errors, 'attachment') ?>
                    <?php if (!empty($task['attachment_path'])): ?>
                        <div class="attachment-current">
                            <a href="<?= View::e($task['attachment_path']) ?>" target="_blank" rel="noopener">
                                <i class="fa-solid fa-paperclip"></i> <?= View::e($task['attachment_name'] ?: 'Xem file') ?>
                            </a>
                            <label><input type="checkbox" name="remove_attachment" value="1"> G&#7905; file</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="description">M&#244; t&#7843;</label>
                <textarea id="description" name="description" rows="4"
                          class="form-control <?= View::invalid($errors, 'description') ?>"><?= View::e($value('description')) ?></textarea>
                <?= View::error($errors, 'description') ?>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="due_date">H&#7841;n ch&#243;t</label>
                    <input type="date" id="due_date" name="due_date"
                           class="form-control <?= View::invalid($errors, 'due_date') ?>"
                           value="<?= View::e($value('due_date')) ?>">
                    <?= View::error($errors, 'due_date') ?>
                </div>

                <div class="form-group half-width">
                    <label for="list_id"><i class="fa-solid fa-layer-group"></i> Thu&#7897;c danh s&#225;ch</label>
                    <?php $selectedList = $value('list_id'); ?>
                    <select name="list_id" id="list_id" class="form-control <?= View::invalid($errors, 'list_id') ?>">
                        <option value="" <?= empty($selectedList) ? 'selected' : '' ?>>C&#244;ng vi&#7879;c (m&#7863;c &#273;&#7883;nh)</option>
                        <?php foreach ($userLists ?? [] as $list): ?>
                            <option value="<?= (int)$list['id'] ?>" <?= ($selectedList != '' && $selectedList == $list['id']) ? 'selected' : '' ?>>
                                <?= View::e($list['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= View::error($errors, 'list_id') ?>
                </div>
            </div>

            <div class="form-group">
                <?php $progress = (int)($value('progress') ?? 0); ?>
                <label for="progress">Ti&#7871;n &#273;&#7897;: <strong id="progressValue"><?= $progress ?>%</strong></label>
                <input type="range" id="progress" name="progress" min="0" max="100" step="10" value="<?= $progress ?>" oninput="document.getElementById('progressValue').textContent=this.value+'%'">
            </div>

            <div class="form-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="is_important" name="is_important" value="1"
                        <?= $isImportant ? 'checked' : '' ?>>
                    <label for="is_important">&#272;&#225;nh d&#7845;u quan tr&#7885;ng <i class="fa-solid fa-star text-warning"></i></label>
                </div>
            </div>

            <div class="form-actions-right">
                <a href="/" class="btn btn-secondary">H&#7911;y</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> L&#432;u thay &#273;&#7893;i
                </button>
            </div>
        </form>
    </div>

    <div class="form-danger-zone">
        <form method="POST" action="/tasks/delete" class="inline-form"
              onsubmit="return confirm('Chuyen cong viec nay vao thung rac?');">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
            <input type="hidden" name="redirect" value="/">
            <button type="submit" class="btn-text text-danger">
                <i class="fa-solid fa-trash"></i> Chuy&#7875;n v&#224;o th&#249;ng r&#225;c
            </button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
