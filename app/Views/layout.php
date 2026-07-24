<?php
use App\Core\Session;
use App\Core\Csrf;
use App\Core\View;

$isLoggedIn = (bool) Session::get('user_id');

// B? l?c dang du?c ch?n, dÃ¯Â¿Â½ng d? bÃ¯Â¿Â½i sÃ¯Â¿Â½ng dÃ¯Â¿Â½ng m?c trong sidebar
$currentFilter = $active_filter ?? ($preSelectedListId ?? 'inbox');
$activePage = $active_page ?? '';
if ($activePage !== '' && !isset($active_filter)) {
    $currentFilter = '';
}

// CÃ¯Â¿Â½c m?c c? d?nh trong sidebar.
// Gom thÃ¯Â¿Â½nh m?ng r?i l?p thay vÃ¯Â¿Â½ copy-paste 8 kh?i HTML g?n gi?ng nhau.
$navItems = [
    ['key' => 'my-day',     'url' => '/tasks?filter=my-day',     'icon' => 'fa-solid fa-sun',                  'label' => "H\u{00F4}m nay",         'tone' => 'warning'],
    ['key' => 'important',  'url' => '/tasks?filter=important',  'icon' => 'fa-solid fa-star',                 'label' => "Quan tr\u{1ECD}ng",      'tone' => 'danger'],
    ['key' => 'planned',    'url' => '/tasks?filter=planned',    'icon' => 'fa-solid fa-calendar-days',        'label' => "C\u{00F3} h\u{1EA1}n ch\u{00F3}t",     'tone' => 'info'],
    ['key' => 'overdue',    'url' => '/tasks?filter=overdue',    'icon' => 'fa-solid fa-triangle-exclamation', 'label' => "Qu\u{00E1} h\u{1EA1}n",         'tone' => 'danger'],
    ['key' => 'inbox',      'url' => '/tasks',                   'icon' => 'fa-solid fa-inbox',                'label' => "C\u{00F4}ng vi\u{1EC7}c",       'tone' => 'primary'],
];

$statusItems = [
    ['key' => 'incomplete', 'url' => '/tasks?filter=incomplete', 'icon' => 'fa-regular fa-circle',       'label' => "Ch\u{01B0}a ho\u{00E0}n th\u{00E0}nh", 'tone' => ''],
    ['key' => 'completed',  'url' => '/tasks?filter=completed',  'icon' => 'fa-solid fa-circle-check',   'label' => "\u{0110}\u{00E3} ho\u{00E0}n th\u{00E0}nh",   'tone' => 'success'],
    ['key' => 'all',        'url' => '/tasks?filter=all',        'icon' => 'fa-solid fa-list-check',     'label' => "T\u{1EA5}t c\u{1EA3}",          'tone' => ''],
];

$toolItems = [
    ['key' => 'kanban',   'url' => '/kanban',   'icon' => 'fa-solid fa-table-columns', 'label' => "Kanban"],
    ['key' => 'calendar', 'url' => '/calendar', 'icon' => 'fa-regular fa-calendar', 'label' => "L\u{1ECB}ch"],
    ['key' => 'report',   'url' => '/report',   'icon' => 'fa-solid fa-chart-simple', 'label' => "B\u{00E1}o c\u{00E1}o"],
    ['key' => 'activity', 'url' => '/activity', 'icon' => 'fa-solid fa-clock-rotate-left', 'label' => "Nh\u{1EAD}t k\u{00FD}"],
];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? 'To-Do MVC') ?></title>

    <?php
    // Ã¯Â¿Â½?t theme TRU?C khi trang v? ra, trÃ¯Â¿Â½nh hi?n tu?ng nhÃ¯Â¿Â½y tr?ng
    // (trang sÃ¯Â¿Â½ng lÃ¯Â¿Â½e lÃ¯Â¿Â½n m?t nh?p r?i m?i chuy?n sang t?i)
    ?>
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (saved === 'dark' || (!saved && prefersDark)) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) { /* localStorage fallback */ }
        })();
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">
    <?php
    // DÃ¯Â¿Â½ng du?ng d?n tuy?t d?i "/css/..." thay vÃ¯Â¿Â½ "../css/...".
    // Ã¯Â¿Â½u?ng d?n tuong d?i ph? thu?c vÃ¯Â¿Â½o URL hi?n t?i nÃ¯Â¿Â½n s? h?ng
    // ? nh?ng trang cÃ¯Â¿Â½ d? sÃ¯Â¿Â½u khÃ¯Â¿Â½c nhau.
    ?>
    <link rel="stylesheet" href="/css/alter_style.css?v=20260722d">
    <link rel="stylesheet" href="/css/profile.css?v=20260722b">
</head>

<body class="bootstrap-layout">

    <nav class="navbar">
        <div class="nav-left">
            <?php if ($isLoggedIn): ?>
                <button type="button" class="icon-btn sidebar-toggle" id="sidebarToggle"
                        aria-label="M&#7903;/&#273;&#243;ng menu" aria-expanded="false">
                    <i class="fa-solid fa-bars"></i>
                </button>
            <?php endif; ?>

            <a href="/" class="nav-brand">
                <i class="fa-solid fa-check-double"></i> <span>To-Do</span>
            </a>
        </div>

        <?php if ($isLoggedIn): ?>
            <div class="nav-user">
                <form class="search-box" action="/tasks/search" method="GET" role="search">
                    <button type="submit" class="search-btn" aria-label="T&#236;m ki&#7871;m">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <input type="text" name="q" placeholder="T&#236;m c&#244;ng vi&#7879;c..."
                           value="<?= View::e($search_query ?? '') ?>" required>
                </form>

                <div class="user-info">
                    <button type="button" class="icon-btn theme-toggle" id="themeToggle"
                            title="&#272;&#7893;i giao di&#7879;n s&#225;ng/t&#7889;i" aria-label="&#272;&#7893;i giao di&#7879;n s&#225;ng/t&#7889;i">
                        <i class="fa-solid fa-moon"></i>
                    </button>

                    <a href="/profile" class="user-profile-link" title="Xem t&#224;i kho&#7843;n">
                        <span class="avatar"><?= View::e(mb_strtoupper(mb_substr((string)Session::get('user_name'), 0, 1))) ?></span>
                    </a>

                    <?php
                    // Ã¯Â¿Â½ang xu?t dÃ¯Â¿Â½ng POST + CSRF thay vÃ¯Â¿Â½ link GET.
                    // N?u d? link GET, k? x?u nhÃ¯Â¿Â½ng <img src="/logout"> lÃ¯Â¿Â½ b?n
                    // b? dÃ¯Â¿Â½ ra kh?i phiÃ¯Â¿Â½n lÃ¯Â¿Â½m vi?c mÃ¯Â¿Â½ khÃ¯Â¿Â½ng hi?u vÃ¯Â¿Â½ sao.
                    ?>
                    <form method="POST" action="/logout" class="inline-form">
                        <?= Csrf::field() ?>
                        <button type="submit" class="icon-btn btn-logout" title="&#272;&#259;ng xu&#7845;t" aria-label="&#272;&#259;ng xu&#7845;t">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </nav>

    <div class="app-container">

        <?php if ($isLoggedIn): ?>
            <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

            <aside class="sidebar" id="sidebar">
                <div class="sidebar-group">
                    <?php foreach ($navItems as $item): ?>
                        <?php
                        $count    = $taskCounts[$item['key']] ?? 0;
                        $isActive = ($currentFilter === $item['key']) ? 'active' : '';
                        // RiÃ¯Â¿Â½ng m?c "QuÃ¯Â¿Â½ h?n" ch? hi?n khi th?c s? cÃ¯Â¿Â½ vi?c quÃ¯Â¿Â½ h?n,
                        // d? sidebar khÃ¯Â¿Â½ng b? r?i lÃ¯Â¿Â½c m?i th? dang ?n
                        if ($item['key'] === 'overdue' && $count === 0 && $currentFilter !== 'overdue') {
                            continue;
                        }
                        ?>
                        <a href="<?= View::e($item['url']) ?>" class="sidebar-item <?= $isActive ?>">
                            <div class="sidebar-item-content">
                                <div class="sidebar-item-label">
                                    <i class="<?= View::e($item['icon']) ?> <?= $item['tone'] ? 'text-' . View::e($item['tone']) : '' ?>"></i>
                                    <span><?= View::e($item['label']) ?></span>
                                </div>
                                <?php if ($count > 0): ?>
                                    <span class="task-count <?= $item['key'] === 'overdue' ? 'count-danger' : '' ?>"><?= (int)$count ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <hr class="sidebar-divider">

                <div class="sidebar-section-title">Tr&#7841;ng th&#225;i</div>
                <div class="sidebar-group">
                    <?php foreach ($statusItems as $item): ?>
                        <?php
                        $count    = $taskCounts[$item['key']] ?? 0;
                        $isActive = ($currentFilter === $item['key']) ? 'active' : '';
                        ?>
                        <a href="<?= View::e($item['url']) ?>" class="sidebar-item <?= $isActive ?>">
                            <div class="sidebar-item-content">
                                <div class="sidebar-item-label">
                                    <i class="<?= View::e($item['icon']) ?> <?= $item['tone'] ? 'text-' . View::e($item['tone']) : '' ?>"></i>
                                    <span><?= View::e($item['label']) ?></span>
                                </div>
                                <?php if ($count > 0): ?>
                                    <span class="task-count"><?= (int)$count ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <hr class="sidebar-divider">

                <div class="sidebar-section-title">C&#244;ng c&#7909;</div>
                <div class="sidebar-group">
                    <?php foreach ($toolItems as $item): ?>
                        <a href="<?= View::e($item['url']) ?>" class="sidebar-item <?= $activePage === $item['key'] ? 'active' : '' ?>">
                            <div class="sidebar-item-content">
                                <div class="sidebar-item-label">
                                    <i class="<?= View::e($item['icon']) ?>"></i>
                                    <span><?= View::e($item['label']) ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <hr class="sidebar-divider">

                <div class="sidebar-section-title">Danh s&#225;ch c&#7911;a t&#244;i</div>
                <div class="sidebar-group scrollable-group">
                    <?php if (!empty($userLists)): ?>
                        <?php foreach ($userLists as $list): ?>
                            <?php
                            // So sÃ¯Â¿Â½nh l?ng (==) d? '5' (string) v?n b?ng 5 (int)
                            $isActive  = ($currentFilter == $list['id']) ? 'active' : '';
                            $listCount = $taskCounts['lists'][$list['id']] ?? 0;
                            ?>
                            <a href="/tasks?list=<?= (int)$list['id'] ?>" class="sidebar-item <?= $isActive ?>">
                                <div class="sidebar-item-content">
                                    <div class="sidebar-item-label">
                                        <i class="fa-solid fa-list-ul"></i>
                                        <span><?= View::e($list['name']) ?></span>
                                    </div>
                                    <?php if ($listCount > 0): ?>
                                        <span class="task-count"><?= (int)$listCount ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="sidebar-hint">Ch&#432;a c&#243; danh s&#225;ch n&#224;o.</p>
                    <?php endif; ?>
                </div>

                <div class="sidebar-footer">
                    <?php $trashCount = $taskCounts['trash'] ?? 0; ?>
                    <?php if ($trashCount > 0 || $currentFilter === 'trash'): ?>
                        <a href="/tasks?filter=trash" class="sidebar-item <?= $currentFilter === 'trash' ? 'active' : '' ?>">
                            <div class="sidebar-item-content">
                                <div class="sidebar-item-label">
                                    <i class="fa-solid fa-trash"></i> <span>Th&#249;ng r&#225;c</span>
                                </div>
                                <?php if ($trashCount > 0): ?>
                                    <span class="task-count"><?= (int)$trashCount ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endif; ?>

                    <a href="/lists/create" class="sidebar-item new-list-btn">
                        <i class="fa-solid fa-plus"></i> <span>Danh s&#225;ch m&#7899;i</span>
                    </a>
                </div>
            </aside>
        <?php endif; ?>

        <main class="main-content container-fluid px-3 px-lg-4">
            <div class="alerts-wrapper">
                <?php if ($msg = Session::getFlash('success')): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i> <span><?= View::e($msg) ?></span>
                        <button type="button" class="alert-close" aria-label="&#272;&#243;ng">&times;</button>
                    </div>
                <?php endif; ?>

                <?php if ($msg = Session::getFlash('error')): ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i> <span><?= View::e($msg) ?></span>
                        <button type="button" class="alert-close" aria-label="&#272;&#243;ng">&times;</button>
                    </div>
                <?php endif; ?>
            </div>

            <?= $content ?? '' ?>
        </main>
    </div>

    <script src="/js/main.js?v=20260722b"></script>
</body>

</html>

