document.addEventListener('DOMContentLoaded', function () {
    var board = document.querySelector('[data-kanban-board]');
    if (!board) return;

    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var draggedCard = null;

    board.querySelectorAll('.kanban-card[draggable="true"]').forEach(function (card) {
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
    });

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

            var taskId = draggedCard.dataset.taskId;
            var progress = column.dataset.progress;
            var currentColumn = draggedCard.closest('.kanban-column');
            if (!taskId || !progress || currentColumn === column) return;

            draggedCard.classList.add('is-saving');
            var body = new URLSearchParams({
                _token: csrf,
                id: taskId,
                progress: progress,
                redirect: '/kanban',
            });

            fetch('/tasks/progress', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body,
            }).then(function (response) {
                if (!response.ok) throw new Error('Kanban update failed');
                window.location.reload();
            }).catch(function () {
                draggedCard.classList.remove('is-saving');
                window.alert('Không thể cập nhật trạng thái công việc.');
            });
        });
    });
});
