// Form công việc: lọc ô "Giao cho" theo nhóm đang chọn, và ẩn/hiện ngày kết thúc lặp.
document.addEventListener('DOMContentLoaded', function () {
    var mapEl = document.getElementById('team-members-map');
    var teamSelect = document.getElementById('team_id');
    var assigneeSelect = document.getElementById('assignee_id');

    if (mapEl && teamSelect && assigneeSelect) {
        var teamMembers = {};
        try { teamMembers = JSON.parse(mapEl.textContent || '{}'); } catch (e) { teamMembers = {}; }

        var initial = assigneeSelect.value;

        function syncAssignees() {
            var raw = teamSelect.value;
            // Chỉ nhóm mới giao việc được; "list_x" và rỗng đều là việc cá nhân.
            var isTeam = raw !== '' && raw.indexOf('list_') !== 0;
            var members = isTeam ? (teamMembers[raw] || []) : [];
            var previous = assigneeSelect.value || initial;

            assigneeSelect.innerHTML = '';

            var blank = document.createElement('option');
            blank.value = '';
            blank.textContent = isTeam ? '-- Chưa giao cho ai --' : '-- Chọn nhóm trước để giao việc --';
            assigneeSelect.appendChild(blank);

            members.forEach(function (member) {
                var option = document.createElement('option');
                option.value = member.id;
                option.textContent = member.name;
                if (String(member.id) === String(previous)) option.selected = true;
                assigneeSelect.appendChild(option);
            });

            assigneeSelect.disabled = !isTeam;
        }

        teamSelect.addEventListener('change', syncAssignees);
        syncAssignees();
    }

    var repeatSelect = document.querySelector('[data-repeat-toggle]');
    var repeatUntil = document.querySelector('[data-repeat-until-wrapper]');
    if (repeatSelect && repeatUntil) {
        repeatSelect.addEventListener('change', function () {
            repeatUntil.style.display = repeatSelect.value === 'none' ? 'none' : '';
        });
    }
});
