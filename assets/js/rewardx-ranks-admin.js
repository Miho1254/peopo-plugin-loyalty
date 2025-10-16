(function () {
    'use strict';

    function createRowId() {
        return 'rewardx-rank-' + Math.random().toString(36).slice(2, 10);
    }

    function handleAddRow(event) {
        event.preventDefault();

        var template = document.getElementById('tmpl-rewardx-rank-row');
        if (!template) {
            return;
        }

        var tbody = document.querySelector('#rewardx-ranks-table tbody');
        if (!tbody) {
            return;
        }

        var html = template.innerHTML.replace(/{{\s*data\.id\s*}}/g, createRowId());
        var wrapper = document.createElement('tbody');
        wrapper.innerHTML = html.trim();
        var row = wrapper.firstElementChild;

        if (row) {
            tbody.appendChild(row);
        }
    }

    function handleRemoveRow(event) {
        if (!event.target.classList.contains('rewardx-remove-rank')) {
            return;
        }

        event.preventDefault();

        if (window.rewardxRanksAdmin && rewardxRanksAdmin.confirmRemove) {
            if (!window.confirm(rewardxRanksAdmin.confirmRemove)) {
                return;
            }
        }

        var row = event.target.closest('tr');
        if (row) {
            var tbody = row.parentElement;
            row.remove();

            if (tbody && tbody.children.length === 0) {
                handleAddRow(event);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var addButton = document.querySelector('.rewardx-add-rank');
        if (addButton) {
            addButton.addEventListener('click', handleAddRow);
        }

        var table = document.getElementById('rewardx-ranks-table');
        if (table) {
            table.addEventListener('click', handleRemoveRow);
        }
    });
})();
