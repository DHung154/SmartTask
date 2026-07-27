// Kanban kéo thả: cập nhật thẳng trên giao diện, không tải lại trang.
document.addEventListener('DOMContentLoaded', function () {
    var board = document.querySelector('[data-kanban-board]');
    if (!board) return;

    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var toast = document.querySelector('[data-kanban-toast]');
    var draggedCard = null;
    var toastTimer = null;

    function showToast(message, isError) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.toggle('is-error', !!isError);
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.hidden = true; }, 2500);
    }

    // Cập nhật số đếm và dòng "trống" của mọi cột.
    function refreshColumns() {
        board.querySelectorAll('.kanban-column').forEach(function (column) {
            var cards = column.querySelectorAll('.kanban-card').length;
            var counter = column.querySelector('[data-column-count]');
            var empty = column.querySelector('[data-kanban-empty]');
            if (counter) counter.textContent = cards;
            if (empty) empty.style.display = cards ? 'none' : '';
        });
    }

    function bindCard(card) {
        card.addEventListener('dragstart', function (event) {
            draggedCard = card;
            card.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.taskId);
        });

        card.addEventListener('dragend', function () {
            card.classList.remove('is-dragging');
            board.querySelectorAll('.drag-over').forEach(function (column) { column.classList.remove('drag-over'); });
            draggedCard = null;
        });
    }

    board.querySelectorAll('.kanban-card[draggable="true"]').forEach(bindCard);

    board.querySelectorAll('.kanban-column').forEach(function (column) {
        column.addEventListener('dragover', function (event) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            column.classList.add('drag-over');
        });

        column.addEventListener('dragleave', function (event) {
            if (!column.contains(event.relatedTarget)) column.classList.remove('drag-over');
        });

        column.addEventListener('drop', function (event) {
            event.preventDefault();
            column.classList.remove('drag-over');
            if (!draggedCard) return;

            var card = draggedCard;
            var taskId = card.dataset.taskId;
            var status = column.dataset.status;
            var sourceColumn = card.closest('.kanban-column');
            if (!taskId || !status || sourceColumn === column) return;

            // Chuyển thẻ ngay để thao tác thấy mượt, lỗi thì trả về chỗ cũ.
            var list = column.querySelector('[data-kanban-list]');
            var nextSibling = card.nextSibling;
            list.appendChild(card);
            card.classList.add('is-saving');
            refreshColumns();

            fetch('/tasks/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new URLSearchParams({ _token: csrf, id: taskId, status: status }),
            }).then(function (response) {
                if (!response.ok) throw new Error('status update failed');
                return response.json();
            }).then(function (payload) {
                var progress = payload && payload.data ? payload.data.progress : null;
                if (progress !== null && progress !== undefined) {
                    var bar = card.querySelector('[data-progress-bar]');
                    var text = card.querySelector('[data-progress-text]');
                    if (bar) bar.style.width = progress + '%';
                    if (text) text.textContent = progress + '%';
                }
                card.classList.remove('is-saving');
            }).catch(function () {
                // Trả thẻ về đúng vị trí cũ.
                var sourceList = sourceColumn.querySelector('[data-kanban-list]');
                if (nextSibling && nextSibling.parentNode === sourceList) {
                    sourceList.insertBefore(card, nextSibling);
                } else {
                    sourceList.appendChild(card);
                }
                card.classList.remove('is-saving');
                refreshColumns();
                showToast('Không cập nhật được trạng thái công việc.', true);
            });
        });
    });

    refreshColumns();
});
