(function() {
    const { __ } = wp.i18n;

    function init() {
        const table = document.getElementById('ysg-shorts-table');
        const addButton = document.getElementById('ysg-add-row');
        if (!table || !addButton) {
            return;
        }

        let rowIndex = table.querySelectorAll('tbody .ysg-row').length;

        addButton.addEventListener('click', () => {
            const template = document.getElementById('tmpl-ysg-row-template');
            if (!template) {
                return;
            }

            const html = template.innerHTML.replace(/\{\{index\}\}/g, String(rowIndex));
            rowIndex += 1;

            const temp = document.createElement('tbody');
            temp.innerHTML = html;
            const newRow = temp.firstElementChild;
            if (newRow) {
                table.querySelector('tbody').appendChild(newRow);
            }
        });

        table.addEventListener('click', (event) => {
            const target = event.target;
            if (target && target.classList.contains('ysg-remove-row')) {
                event.preventDefault();
                const row = target.closest('.ysg-row');
                if (row) {
                    row.remove();
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
