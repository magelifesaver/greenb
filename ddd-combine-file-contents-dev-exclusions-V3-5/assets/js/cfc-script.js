/* ---------------------------------------------------------------
 * File: assets/js/cfc-script.js
 * Version: 3.2.5 · 2025-06-18
 * Purpose:
 *   • Tree UI (expand / collapse / select)
 *   • One-shot persistence: selections survive one reload only
 *   • NEW: “Clear Selections” button wipes all checks + memory
 * ------------------------------------------------------------- */
(function () {
  'use strict';

  const EXP_KEY = 'cfc_expanded';
  const SEL_KEY = 'cfc_selected';

  /* read saved state once, then clear keys so it is one-time only */
  const EXP_SAVED = JSON.parse(localStorage.getItem(EXP_KEY) || '[]');
  const SEL_SAVED = JSON.parse(localStorage.getItem(SEL_KEY) || '[]');
  localStorage.removeItem(EXP_KEY);
  localStorage.removeItem(SEL_KEY);

  /* helper to write arrays back to storage for the next auto-reload */
  const save = (key, arr) =>
    localStorage.setItem(key, JSON.stringify(Array.from(new Set(arr))));

  document.addEventListener('DOMContentLoaded', function () {

    /* attach listeners to all existing list items */
    document
      .querySelectorAll('#cfc-directory-tree-container li')
      .forEach(attachListeners);

    /* restore previous state (folders open, items checked) */
    restoreExpanded();
    restoreSelected();

    /* --------------------------------------------------------
     * Clear Selections button
     * ------------------------------------------------------ */
    const clearBtn = document.getElementById('cfc-clear-btn');
    if (clearBtn) {
      clearBtn.addEventListener('click', function () {

        /* 1. un-check every box and remove highlight classes */
        document
          .querySelectorAll('#cfc-directory-tree-container input.cfc-v2-checkbox')
          .forEach(cb => {
            cb.checked = false;
            const li = cb.closest('li');
            li.classList.remove('selected-folder', 'selected-file');
          });

        /* 2. remove any saved state so next reload starts fresh */
        localStorage.removeItem(EXP_KEY);
        localStorage.removeItem(SEL_KEY);

        /* 3. OPTIONAL: collapse all open folders
           uncomment the block below if desired
        document
          .querySelectorAll('#cfc-directory-tree-container li.expanded')
          .forEach(li => {
            const icon = li.querySelector('.cfc-v2-expand-icon');
            if (icon) icon.click();
          });
        */
      });
    }

    /* ---------- helper definitions ---------- */

    function attachListeners(li) {
      const cb     = li.querySelector('input.cfc-v2-checkbox');
      const name   = li.querySelector('.cfc-v2-folder-name');
      const icon   = li.querySelector('.cfc-v2-expand-icon');

      if (cb) {
        cb.addEventListener('change', function () {
          cascadeSelect(li, cb.checked);
          updateSelectedStorage();
        });
        if (cb.checked) markSelected(li, true);
      }

      if (name) {
        name.addEventListener('click', function (e) {
          e.stopPropagation();
          toggleNode(li, name.dataset.rel);
        });
      }

      if (icon) {
        icon.addEventListener('click', function (e) {
          e.stopPropagation();
          toggleNode(li, icon.previousElementSibling.dataset.rel);
        });
      }
    }

    function markSelected(li, checked) {
      li.classList.toggle(
        'selected-folder',
        checked && !!li.querySelector('.cfc-v2-folder-name')
      );
      li.classList.toggle(
        'selected-file',
        checked && !!li.querySelector('.cfc-v2-file-name')
      );
    }

    function cascadeSelect(rootLi, checked) {
      rootLi.querySelectorAll('input.cfc-v2-checkbox').forEach(cb => {
        cb.checked = checked;
        markSelected(cb.closest('li'), checked);
      });
    }

    function toggleNode(li, rel) {
      const childUl = li.querySelector('ul.cfc-v2-tree');
      const icon    = li.querySelector('.cfc-v2-expand-icon');
      const open    = li.classList.contains('expanded');

      if (open) {
        li.classList.remove('expanded');
        if (childUl) childUl.style.display = 'none';
        if (icon) icon.textContent = '>';
      } else {
        li.classList.add('expanded');
        if (icon) icon.textContent = 'v';

        /* lazy-load children if not loaded yet */
        if (childUl && childUl.children.length === 0) {
          jQuery.post(ajaxurl, { action: 'ddd_cfc_fetch_children', parent: rel })
            .done(function (res) {
              if (!res.success) return;

              res.data.forEach(item => {
                const childRel = rel + '/' + item.name;
                const childLi  = document.createElement('li');

                /* checkbox + label */
                let html = '<label><input type="checkbox" class="cfc-v2-checkbox" ' +
                           'name="cfc_items[]" value="' + childRel + '"' +
                           (SEL_SAVED.includes(childRel) ? ' checked' : '') +
                           '></label> ';

                if (item.type === 'folder') {
                  html += '<span class="cfc-v2-folder-name" data-rel="' + childRel + '">' +
                          item.name + '</span>';
                  if (item.hasChildren) {
                    html += ' <span class="cfc-v2-expand-icon">></span>' +
                            '<ul class="cfc-v2-tree" style="display:none;"></ul>';
                  }
                } else {
                  html += '<span class="cfc-v2-file-name" data-rel="' + childRel + '">' +
                          item.name + '</span>';
                }

                childLi.innerHTML = html;
                childUl.appendChild(childLi);
                attachListeners(childLi);

                /* auto-expand if it was open previously */
                if (EXP_SAVED.includes(childRel)) {
                  toggleNode(childLi, childRel);
                }
              });

              /* if parent checkbox is checked, keep new children checked */
              if (li.querySelector('input.cfc-v2-checkbox').checked) {
                cascadeSelect(li, true);
              }
            });
        }

        if (childUl) childUl.style.display = 'block';
      }

      updateExpandedStorage();
    }

    /* ---------- persistence for the next auto-reload ---------- */

    function updateExpandedStorage() {
      const open = Array.from(
        document.querySelectorAll('#cfc-directory-tree-container li.expanded')
      )
        .map(li => li.querySelector('.cfc-v2-folder-name')?.dataset.rel)
        .filter(Boolean);
      save(EXP_KEY, open);
    }

    function updateSelectedStorage() {
      const sel = Array.from(
        document.querySelectorAll(
          '#cfc-directory-tree-container input.cfc-v2-checkbox:checked'
        )
      ).map(cb => cb.value);
      save(SEL_KEY, sel);
    }

    /* ---------- one-time restore using in-memory copies ---------- */

    function restoreExpanded() {
      EXP_SAVED
        .sort((a, b) => a.split('/').length - b.split('/').length)
        .forEach(rel => {
          const span = document.querySelector(
            '.cfc-v2-folder-name[data-rel="' + rel + '"]'
          );
          if (span) {
            const li = span.closest('li');
            if (!li.classList.contains('expanded')) {
              toggleNode(li, rel);
            }
          }
        });
    }

    function restoreSelected() {
      SEL_SAVED.forEach(rel => {
        const cb = document.querySelector(
          'input.cfc-v2-checkbox[value="' + rel + '"]'
        );
        if (cb) {
          cb.checked = true;
          markSelected(cb.closest('li'), true);
        }
      });
    }
  });
})();
