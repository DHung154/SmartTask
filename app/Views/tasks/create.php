<?php
use App\Core\Csrf;
use App\Core\View;

$title = "Th\u{00EA}m c\u{00F4}ng vi\u{1EC7}c";
$old = $old ?? [];
$errors = $errors ?? [];
$selectedList = $old['list_id'] ?? $preSelectedListId ?? '';

ob_start();
?>

<div class="form-wrapper">
    <div class="content-header">
        <div class="header-title">
            <h1><i class="fa-solid fa-circle-plus"></i> Th&#234;m c&#244;ng vi&#7879;c</h1>
        </div>
        <a href="/" class="btn-text"><i class="fa-solid fa-arrow-left"></i> V&#7873; danh s&#225;ch</a>
    </div>

    <div class="form-card">
        <form method="POST" action="/tasks/create" enctype="multipart/form-data" novalidate>
            <?= Csrf::field() ?>

            <div class="form-group">
                <label for="title">T&#234;n c&#244;ng vi&#7879;c <span class="required">*</span></label>
                <input type="text" id="title" name="title" maxlength="200" autofocus
                       class="form-control <?= View::invalid($errors, 'title') ?>"
                       placeholder="B&#7841;n c&#7847;n l&#224;m g&#236;?"
                       value="<?= View::old($old, 'title') ?>">
                <?= View::error($errors, 'title') ?>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="priority">M&#7913;c &#432;u ti&#234;n</label>
                    <?php $priority = $old['priority'] ?? 'normal'; ?>
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
                </div>
            </div>

            <div class="form-group">
                <label for="description">M&#244; t&#7843;</label>
                <textarea id="description" name="description" rows="4"
                          class="form-control <?= View::invalid($errors, 'description') ?>"
                          placeholder="Th&#234;m chi ti&#7871;t..."><?= View::old($old, 'description') ?></textarea>
                <?= View::error($errors, 'description') ?>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="due_date">H&#7841;n ch&#243;t (kh&#244;ng b&#7855;t bu&#7897;c)</label>
                    <input type="date" id="due_date" name="due_date"
                           class="form-control <?= View::invalid($errors, 'due_date') ?>"
                           value="<?= View::old($old, 'due_date') ?>">
                    <?= View::error($errors, 'due_date') ?>
                </div>

                <div class="form-group half-width">
                    <label for="list_id"><i class="fa-solid fa-layer-group"></i> Thu&#7897;c danh s&#225;ch</label>
                    <select name="list_id" id="list_id" class="form-control <?= View::invalid($errors, 'list_id') ?>">
                        <option value="" <?= (!is_numeric($selectedList)) ? 'selected' : '' ?>>C&#244;ng vi&#7879;c (m&#7863;c &#273;&#7883;nh)</option>
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
                <label for="progress">Ti&#7871;n &#273;&#7897;: <strong id="progressValue"><?= (int)($old['progress'] ?? 0) ?>%</strong></label>
                <input type="range" id="progress" name="progress" min="0" max="100" step="10" value="<?= (int)($old['progress'] ?? 0) ?>" oninput="document.getElementById('progressValue').textContent=this.value+'%'">
            </div>

            <div class="form-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="is_important" name="is_important" value="1"
                        <?= !empty($old['is_important']) ? 'checked' : '' ?>>
                    <label for="is_important">&#272;&#225;nh d&#7845;u quan tr&#7885;ng <i class="fa-solid fa-star text-warning"></i></label>
                </div>
            </div>

            <div class="form-actions-right">
                <a href="/" class="btn btn-secondary">H&#7911;y</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Th&#234;m c&#244;ng vi&#7879;c
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
