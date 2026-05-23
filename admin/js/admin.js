(function() {
    'use strict';

    window.showForm = function() {
        var list = document.getElementById('module-list');
        var form = document.getElementById('module-form');
        if (list) list.style.display = 'none';
        if (form) form.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    window.showList = function() {
        var list = document.getElementById('module-list');
        var form = document.getElementById('module-form');
        if (list) list.style.display = 'block';
        if (form) form.style.display = 'none';
        var idField = document.querySelector('#module-form input[name="id"]');
        if (idField) idField.value = '';
        var formEl = document.getElementById('module-form').querySelector('form');
        if (formEl) formEl.reset();
    };

    window.searchTable = function(input) {
        var q = input.value.toLowerCase();
        var rows = document.querySelectorAll('table tbody tr');
        rows.forEach(function(row) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.indexOf(q) > -1 ? '' : 'none';
        });
    };
})();
