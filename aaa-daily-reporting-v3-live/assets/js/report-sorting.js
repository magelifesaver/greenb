// File: assets/report-sorting.js
// Description: Column-aware multi-table sorter with independent state

(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const stringColumns = ["customer", "status", "city", "payment", "name", "email"];

    const getCellValue = (tr, idx) => {
      const cell = tr.children[idx];
      return cell ? (cell.textContent || cell.innerText).trim() : '';
    };

    const isNumeric = val => /^-?\d+(\.\d+)?$/.test(val.replace(/[^\d.-]/g, ''));

    const comparer = (idx, asc, type) => (a, b) => {
      const v1 = getCellValue(asc ? a : b, idx);
      const v2 = getCellValue(asc ? b : a, idx);

      if (type === 'string') return v1.localeCompare(v2);

      const num1 = parseFloat(v1.replace(/[^\d.-]/g, ''));
      const num2 = parseFloat(v2.replace(/[^\d.-]/g, ''));

      if (!isNaN(num1) && !isNaN(num2)) return num1 - num2;
      return v1.localeCompare(v2);
    };

    document.querySelectorAll('table.sortable').forEach(table => {
      const headers = table.querySelectorAll('thead th');

      headers.forEach((th, i) => {
        th.style.cursor = 'pointer';
        th.dataset.sortDir = 'asc';
        th.addEventListener('click', function () {
          const tbody = table.querySelector('tbody');
          const header = th.textContent.toLowerCase();
          const type = stringColumns.some(key => header.includes(key)) ? 'string' : 'auto';
          const asc = th.dataset.sortDir === 'asc';

          const rows = Array.from(tbody.querySelectorAll('tr'));
          rows.sort(comparer(i, asc, type));
          rows.forEach(tr => tbody.appendChild(tr));

          // toggle direction
          th.dataset.sortDir = asc ? 'desc' : 'asc';
        });
      });
    });
  });
})();