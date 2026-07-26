@php
$currentFilter = $active_filter ?? ($preSelectedListId ?? 'inbox');
$activePage = $active_page ?? '';
if ($activePage !== '' && !isset($active_filter)) {
    $currentFilter = '';
}

$navItems = [
    ['key' => 'my-day',     'url' => '/tasks?filter=my-day',     'icon' => 'fa-solid fa-sun',                  'label' => __('nav.today'),     'tone' => 'warning'],
    ['key' => 'important',  'url' => '/tasks?filter=important',  'icon' => 'fa-solid fa-star',                 'label' => __('nav.important'),  'tone' => 'danger'],
    ['key' => 'planned',    'url' => '/tasks?filter=planned',    'icon' => 'fa-solid fa-calendar-days',        'label' => __('nav.planned'),    'tone' => 'info'],
    ['key' => 'overdue',    'url' => '/tasks?filter=overdue',    'icon' => 'fa-solid fa-triangle-exclamation', 'label' => __('nav.overdue'),    'tone' => 'danger'],
    ['key' => 'inbox',      'url' => '/tasks',                   'icon' => 'fa-solid fa-inbox',                'label' => __('nav.tasks'),      'tone' => 'primary'],
];

$statusItems = [
    ['key' => 'incomplete', 'url' => '/tasks?filter=incomplete', 'icon' => 'fa-regular fa-circle',       'label' => __('status.incomplete'), 'tone' => ''],
    ['key' => 'completed',  'url' => '/tasks?filter=completed',  'icon' => 'fa-solid fa-circle-check',   'label' => __('status.completed'),  'tone' => 'success'],
    ['key' => 'all',        'url' => '/tasks?filter=all',        'icon' => 'fa-solid fa-list-check',     'label' => __('status.all'),        'tone' => ''],
];

$toolItems = [
    ['key' => 'teams',    'url' => '/teams',    'icon' => 'fa-solid fa-users',         'label' => __('nav.teams')],
    ['key' => 'kanban',   'url' => '/kanban',   'icon' => 'fa-solid fa-table-columns', 'label' => __('nav.kanban')],
    ['key' => 'calendar', 'url' => '/calendar', 'icon' => 'fa-regular fa-calendar',    'label' => __('nav.calendar')],
    ['key' => 'report',   'url' => '/report',   'icon' => 'fa-solid fa-chart-simple',  'label' => __('nav.reports')],
    ['key' => 'activity', 'url' => '/activity', 'icon' => 'fa-solid fa-clock-rotate-left', 'label' => __('nav.activity')],
];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'To-Do MVC' }}</title>

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
    <link rel="stylesheet" href="/css/alter_style.css?v=20260722d">
    <link rel="stylesheet" href="/css/profile.css?v=20260722b">
    <link rel="stylesheet" href="/css/locale.css?v=20260727a">
</head>

<body class="bootstrap-layout">

    <nav class="navbar">
        <div class="nav-left">
            @auth
                <button type="button" class="icon-btn sidebar-toggle" id="sidebarToggle"
                        aria-label="{{ __('common.toggle_menu') }}" aria-expanded="false">
                    <i class="fa-solid fa-bars"></i>
                </button>
            @endauth

            <a href="/" class="nav-brand">
                <i class="fa-solid fa-check-double"></i> <span>To-Do</span>
            </a>
        </div>

        @auth
            <div class="nav-user">
                <form class="search-box" action="/tasks/search" method="GET" role="search">
                    <button type="submit" class="search-btn" aria-label="{{ __('common.search') }}">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <input type="text" name="q" placeholder="{{ __('common.search') }}"
                           value="{{ $search_query ?? '' }}" required>
                </form>

                <div class="user-info">
                    <form method="POST" action="/locale" class="locale-form" title="{{ __('common.language') }}">
                        @csrf
                        <select name="locale" aria-label="{{ __('common.language') }}" onchange="this.form.submit()">
                            <option value="vi" @selected(app()->getLocale() === 'vi')>VI</option>
                            <option value="en" @selected(app()->getLocale() === 'en')>EN</option>
                        </select>
                    </form>

                    <button type="button" class="icon-btn theme-toggle" id="themeToggle"
                            title="{{ __('common.toggle_theme') }}" aria-label="{{ __('common.toggle_theme') }}">
                        <i class="fa-solid fa-moon"></i>
                    </button>

                    <a href="/profile" class="user-profile-link" title="{{ __('common.account') }}">
                        <span class="avatar">{{ mb_strtoupper(mb_substr((string)auth()->user()->name, 0, 1)) }}</span>
                    </a>

                    <form method="POST" action="/logout" class="inline-form">
                        @csrf
                        <button type="submit" class="icon-btn btn-logout" title="{{ __('common.logout') }}" aria-label="{{ __('common.logout') }}">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </nav>

    <div class="app-container">

        @auth
            <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

            <aside class="sidebar" id="sidebar">
                <div class="sidebar-group">
                    @foreach ($navItems as $item)
                        @php
                        $count    = $taskCounts[$item['key']] ?? 0;
                        $isActive = ($currentFilter === $item['key']) ? 'active' : '';
                        if ($item['key'] === 'overdue' && $count === 0 && $currentFilter !== 'overdue') {
                            continue;
                        }
                        @endphp
                        <a href="{{ $item['url'] }}" class="sidebar-item {{ $isActive }}">
                            <div class="sidebar-item-content">
                                <div class="sidebar-item-label">
                                    <i class="{{ $item['icon'] }} {{ $item['tone'] ? 'text-' . $item['tone'] : '' }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                </div>
                                @if ($count > 0)
                                    <span class="task-count {{ $item['key'] === 'overdue' ? 'count-danger' : '' }}">{{ (int)$count }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <hr class="sidebar-divider">

                <div class="sidebar-section-title">{{ __('sidebar.status') }}</div>
                <div class="sidebar-group">
                    @foreach ($statusItems as $item)
                        @php
                        $count    = $taskCounts[$item['key']] ?? 0;
                        $isActive = ($currentFilter === $item['key']) ? 'active' : '';
                        @endphp
                        <a href="{{ $item['url'] }}" class="sidebar-item {{ $isActive }}">
                            <div class="sidebar-item-content">
                                <div class="sidebar-item-label">
                                    <i class="{{ $item['icon'] }} {{ $item['tone'] ? 'text-' . $item['tone'] : '' }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                </div>
                                @if ($count > 0)
                                    <span class="task-count">{{ (int)$count }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <hr class="sidebar-divider">

                <div class="sidebar-section-title">{{ __('sidebar.tools') }}</div>
                <div class="sidebar-group">
                    @foreach ($toolItems as $item)
                        <a href="{{ $item['url'] }}" class="sidebar-item {{ $activePage === $item['key'] ? 'active' : '' }}">
                            <div class="sidebar-item-content">
                                <div class="sidebar-item-label">
                                    <i class="{{ $item['icon'] }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <hr class="sidebar-divider">

                <div class="sidebar-section-title">{{ __('sidebar.my_lists') }}</div>
                <div class="sidebar-group scrollable-group">
                    @if (!empty($userLists))
                        @foreach ($userLists as $list)
                            @php
                            $isActive  = ($currentFilter == $list['id']) ? 'active' : '';
                            $listCount = $taskCounts['lists'][$list['id']] ?? 0;
                            @endphp
                            <a href="/tasks?list={{ (int)$list['id'] }}" class="sidebar-item {{ $isActive }}">
                                <div class="sidebar-item-content">
                                    <div class="sidebar-item-label">
                                        <i class="fa-solid fa-list-ul"></i>
                                        <span>{{ $list['name'] }}</span>
                                    </div>
                                    @if ($listCount > 0)
                                        <span class="task-count">{{ (int)$listCount }}</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    @else
                        <p class="sidebar-hint">{{ __('sidebar.no_lists') }}</p>
                    @endif
                </div>

                <div class="sidebar-footer">
                    @php $trashCount = $taskCounts['trash'] ?? 0; @endphp
                    @if ($trashCount > 0 || $currentFilter === 'trash')
                        <a href="/tasks?filter=trash" class="sidebar-item {{ $currentFilter === 'trash' ? 'active' : '' }}">
                            <div class="sidebar-item-content">
                                <div class="sidebar-item-label">
                                    <i class="fa-solid fa-trash"></i> <span>{{ __('sidebar.trash') }}</span>
                                </div>
                                @if ($trashCount > 0)
                                    <span class="task-count">{{ (int)$trashCount }}</span>
                                @endif
                            </div>
                        </a>
                    @endif

                    <a href="/lists/create" class="sidebar-item new-list-btn">
                        <i class="fa-solid fa-plus"></i> <span>{{ __('sidebar.new_list') }}</span>
                    </a>
                </div>
            </aside>
        @endauth

        <main class="main-content container-fluid px-3 px-lg-4">
            <div class="alerts-wrapper">
                @if (session('success'))
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i> <span>{{ session('success') }}</span>
                        <button type="button" class="alert-close" aria-label="{{ __('common.close') }}">&times;</button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i> <span>{{ session('error') }}</span>
                        <button type="button" class="alert-close" aria-label="{{ __('common.close') }}">&times;</button>
                    </div>
                @endif
            </div>

            @yield('content')
        </main>
    </div>

    <script src="/js/main.js?v=20260722b"></script>
</body>

</html>
