// Chuông thông báo: hover đã mở panel bằng CSS, phần này lo click / bàn phím
// và tự làm mới số thông báo mà không cần tải lại trang.
document.addEventListener('DOMContentLoaded', function () {
    var bell = document.getElementById('notifBell');
    var toggle = document.getElementById('notifToggle');
    if (!bell || !toggle) return;

    function setOpen(open) {
        bell.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', String(open));
    }

    toggle.addEventListener('click', function (event) {
        event.stopPropagation();
        setOpen(!bell.classList.contains('open'));
    });

    document.addEventListener('click', function (event) {
        if (!bell.contains(event.target)) setOpen(false);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') setOpen(false);
    });

    // Chuột rời khỏi chuông thì bỏ trạng thái mở do click, trả lại cho hover.
    bell.addEventListener('mouseleave', function () {
        setOpen(false);
    });

    // ---- Tự làm mới ----
    var POLL_MS = 20000;
    var lastCount = parseInt(bell.dataset.count || '0', 10);

    function renderBadge(count) {
        var badge = toggle.querySelector('.notif-badge');

        if (count <= 0) {
            if (badge) badge.remove();
            return;
        }

        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'notif-badge';
            toggle.appendChild(badge);
        }

        badge.textContent = count > 9 ? '9+' : String(count);

        if (count > lastCount) {
            badge.classList.remove('is-bump');
            // Ép trình duyệt chạy lại animation.
            void badge.offsetWidth;
            badge.classList.add('is-bump');
        }
    }

    function poll() {
        // Đang mở panel thì khoan đổi nội dung dưới tay người dùng.
        if (bell.classList.contains('open') || bell.matches(':hover')) return;

        fetch('/notifications/feed', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
        }).then(function (response) {
            if (!response.ok) throw new Error('feed failed');
            return response.json();
        }).then(function (payload) {
            var count = parseInt(payload.count || 0, 10);
            renderBadge(count);

            // Có thông báo mới thì nạp lại panel để nội dung khớp con số.
            if (count !== lastCount) {
                var headCount = bell.querySelector('.notif-head-count');
                if (headCount) headCount.textContent = count;
                bell.dataset.stale = count > lastCount ? '1' : '';
            }

            lastCount = count;
        }).catch(function () { /* mất mạng tạm thời thì bỏ qua, lần sau thử lại */ });
    }

    setInterval(poll, POLL_MS);

    // Mở chuông mà nội dung đã cũ thì tải lại panel từ máy chủ.
    toggle.addEventListener('mouseenter', function () {
        if (bell.dataset.stale === '1') {
            bell.dataset.stale = '';
            refreshPanel();
        }
    });

    function refreshPanel() {
        fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        }).then(function (response) {
            if (!response.ok) throw new Error('reload failed');
            return response.text();
        }).then(function (html) {
            var parsed = new DOMParser().parseFromString(html, 'text/html');
            var fresh = parsed.querySelector('#notifBell .notif-panel');
            var current = bell.querySelector('.notif-panel');
            if (fresh && current) current.replaceWith(fresh);
        }).catch(function () { /* giữ nguyên panel cũ */ });
    }
});
