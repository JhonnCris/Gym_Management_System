(function () {
    function normalize(value) {
        return String(value || '').trim().toLowerCase();
    }

    function wireTableSearch(inputId, tableId, emptyMessage) {
        const input = document.getElementById(inputId);
        const table = document.getElementById(tableId);

        if (!input || !table) {
            return;
        }

        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr')).filter((row) => !row.querySelector('.empty-cell'));
        let emptyRow = tbody.querySelector('.live-empty-row');

        function ensureEmptyRow() {
            if (emptyRow) {
                return emptyRow;
            }

            emptyRow = document.createElement('tr');
            emptyRow.className = 'live-empty-row';
            emptyRow.innerHTML = `<td colspan="${table.querySelectorAll('thead th').length}" class="empty-cell">${emptyMessage}</td>`;
            emptyRow.style.display = 'none';
            tbody.appendChild(emptyRow);
            return emptyRow;
        }

        function applyFilter() {
            const term = normalize(input.value);
            let visibleCount = 0;

            rows.forEach((row) => {
                const text = normalize(row.textContent);
                const isMatch = !term || text.includes(term);
                row.style.display = isMatch ? '' : 'none';

                if (isMatch) {
                    visibleCount += 1;
                }
            });

            const emptyState = ensureEmptyRow();
            emptyState.style.display = visibleCount === 0 ? '' : 'none';
        }

        let debounceTimer = null;
        input.addEventListener('input', () => {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(applyFilter, 120);
        });

        applyFilter();
    }

    wireTableSearch('membersSearch', 'membersTable', 'No members matched your search.');
    wireTableSearch('equipmentSearch', 'equipmentTable', 'No equipment matched your search.');
})();
