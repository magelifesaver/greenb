/*
 * File: assets/js/admin-extended.js
 *
 * Extended sessions UI adding bulk selection, last activity, customer and cart columns and
 * AJAX end-session handling.  Uses AAA_AC.ajax and AAA_AC.nonce passed from PHP.
 */
(function(){
    function qs(id){ return document.getElementById(id); }
    function on(el,ev,fn){ if(el) el.addEventListener(ev,fn); }

    document.addEventListener('DOMContentLoaded', function(){
        // ---- Sessions tab ----
        var roleSel   = qs('aaa_ac_role');
        var loadBtn   = qs('aaa_ac_load');
        var clearBtn  = qs('aaa_ac_clear');
        var rows      = qs('aaa_ac_rows');
        var onlineOnly= qs('aaa_ac_online_only');
        var endSelBtn = qs('aaa_ac_end_selected');
        var selectAll = qs('aaa_ac_select_all');

        // Load sessions via extended handler
        function loadSessions(){
            if(!roleSel || !roleSel.value){
            // Use correct colspan matching the number of table columns (12)
            rows.innerHTML = '<tr><td colspan="12" style="text-align:center;">No role selected.</td></tr>';
                return;
            }
            rows.innerHTML = '<tr><td colspan="12" style="text-align:center;">Loading…</td></tr>';
            var url = AAA_AC.ajax + '?action=aaa_ac_load_sessions_extended&role=' +
                encodeURIComponent(roleSel.value) + '&nonce=' + encodeURIComponent(AAA_AC.nonce);
            if(onlineOnly && onlineOnly.checked){
                url += '&online_only=1';
            }
            fetch(url,{credentials:'same-origin'})
                .then(function(r){return r.json();})
                .then(function(res){
                    if(res && res.success){
                        rows.innerHTML = res.data.rows || '<tr><td colspan="12" style="text-align:center;color:#777;">No users.</td></tr>';
                        attachRowEvents();
                        // Sorting handlers need to be attached after rows load
                        // but header is static; ensure sort handlers attached once
                        if (typeof window.__aaaAcSortAttached === 'undefined') {
                            attachSortHandlers();
                            window.__aaaAcSortAttached = true;
                        }
                    } else {
                        rows.innerHTML = '<tr><td colspan="12">Failed to load.</td></tr>';
                    }
                })
                .catch(function(){
                    rows.innerHTML = '<tr><td colspan="12">Error.</td></tr>';
                });
        }

        // Attach click handlers to end-session buttons in the table
        function attachRowEvents(){
            var endBtns = rows.querySelectorAll('button.aaa-ac-end-one');
            endBtns.forEach(function(btn){
                on(btn,'click',function(){
                    var uid = btn.getAttribute('data-user-id');
                    if(!uid) return;
                    endSessions([uid], function(){
                        var tr = btn.closest('tr');
                        if(tr) tr.remove();
                    });
                });
            });
        }

        // Send AJAX request to end sessions for the provided IDs
        function endSessions(ids, callback){
            var fd = new FormData();
            fd.append('action','aaa_ac_end_sessions');
            fd.append('nonce', AAA_AC.nonce);
            ids.forEach(function(id){ fd.append('user_ids[]', id); });
            fetch(AAA_AC.ajax,{method:'POST', body: fd, credentials:'same-origin'})
                .then(function(r){ return r.json(); })
                .then(function(res){
                    if(res && res.success){
                        if(typeof callback === 'function') callback();
                    } else {
                        alert('Failed to end sessions.');
                    }
                })
                .catch(function(){
                    alert('Error ending sessions.');
                });
        }

        if(loadBtn && rows){ on(loadBtn,'click', loadSessions); }
        if(clearBtn && rows){ on(clearBtn,'click', function(){
            rows.innerHTML = '<tr><td colspan="12" style="text-align:center;color:#777;">No users loaded.</td></tr>';
        }); }

        // Bulk end selected sessions
        if(endSelBtn && rows){ on(endSelBtn,'click', function(){
            var checkboxes = rows.querySelectorAll('input.aaa-ac-sel:checked');
            var ids = [];
            checkboxes.forEach(function(cb){
                var uid = cb.getAttribute('data-user-id');
                if(uid) ids.push(uid);
            });
            if(ids.length === 0){
                alert('No sessions selected.');
                return;
            }
            endSessions(ids, function(){
                checkboxes.forEach(function(cb){
                    var tr = cb.closest('tr');
                    if(tr) tr.remove();
                });
            });
        }); }

        // Select all toggle
        if(selectAll){ on(selectAll,'change', function(){
            var checked = selectAll.checked;
            var cbs = rows.querySelectorAll('input.aaa-ac-sel');
            cbs.forEach(function(cb){ cb.checked = checked; });
        }); }

        /*
         * Column sorting
         *
         * Allow sorting by clicking on table headers. Each column can toggle
         * between ascending and descending order. Sorting is based on
         * numeric `data-sort-value` attributes where available; otherwise
         * falls back to textual comparison. Columns with index 0 (the
         * checkbox selector) and index 10 (action buttons) are not
         * sortable.
         */
        var sortState = {};
        function attachSortHandlers(){
            // Find the header row associated with the sessions table
            var table = rows && rows.parentNode ? rows.parentNode.closest('table') : null;
            if(!table) return;
            var ths = table.querySelectorAll('thead th');
            ths.forEach(function(th, colIndex){
                // Skip select checkbox column and Action column
                if(colIndex === 0 || colIndex === 10) return;
                // Add visual cue for sortable columns
                th.style.cursor = 'pointer';
                th.title = 'Click to sort';
                on(th, 'click', function(){
                    sortTable(colIndex);
                });
            });
        }

        function sortTable(colIndex){
            var tbody = rows;
            if(!tbody) return;
            // Grab row elements as an array
            var trs = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
            if(trs.length < 2) return;
            // Determine current sort direction; default ascending if not sorted yet
            var asc = sortState[colIndex] === 'asc' ? false : true;
            sortState[colIndex] = asc ? 'asc' : 'desc';
            // Sort rows based on numeric or text values
            trs.sort(function(a, b){
                var tdA = a.children[colIndex];
                var tdB = b.children[colIndex];
                var valA = tdA ? tdA.getAttribute('data-sort-value') : null;
                var valB = tdB ? tdB.getAttribute('data-sort-value') : null;
                // Attempt numeric comparison when both have sort values
                if(valA !== null && valB !== null){
                    var numA = parseFloat(valA);
                    var numB = parseFloat(valB);
                    if(!isNaN(numA) && !isNaN(numB)){
                        return asc ? (numA - numB) : (numB - numA);
                    }
                }
                // Fallback to string comparison
                var textA = tdA ? (tdA.textContent || '').trim().toLowerCase() : '';
                var textB = tdB ? (tdB.textContent || '').trim().toLowerCase() : '';
                if(textA < textB) return asc ? -1 : 1;
                if(textA > textB) return asc ? 1 : -1;
                return 0;
            });
            // Append rows in new order
            trs.forEach(function(tr){ tbody.appendChild(tr); });
        }

        // ---- Settings tab (unchanged) ----
        var sRole  = qs('aaa_ac_settings_role');
        var sLoad  = qs('aaa_ac_settings_load');
        var sClear = qs('aaa_ac_settings_clear');
        var sSave  = qs('aaa_ac_settings_save');
        var sRows  = qs('aaa_ac_settings_rows');

        function enableSaveIfRows(){
            if(!sSave) return;
            sSave.disabled = !sRows || !sRows.querySelector('tr[data-uid]');
        }

        if(sLoad && sRows){ on(sLoad, 'click', function(){
            if(!sRole || !sRole.value){
                sRows.innerHTML = '<tr><td colspan="6" style="text-align:center;">No role selected.</td></tr>';
                enableSaveIfRows(); return;
            }
            sRows.innerHTML = '<tr><td colspan="6" style="text-align:center;">Loading…</td></tr>';
            var url = AAA_AC.ajax + '?action=aaa_ac_load_settings_users_v2&role=' +
                encodeURIComponent(sRole.value) + '&nonce=' + encodeURIComponent(AAA_AC.nonce);
            fetch(url,{credentials:'same-origin'}).then(function(r){ return r.json(); }).then(function(res){
                if(res && res.success){
                    sRows.innerHTML = res.data.rows || '<tr><td colspan="6" style="text-align:center;color:#777;">No users.</td></tr>';
                } else {
                    sRows.innerHTML = '<tr><td colspan="6">Failed to load.</td></tr>';
                }
                enableSaveIfRows();
            }).catch(function(){
                sRows.innerHTML = '<tr><td colspan="6">Error.</td></tr>';
                enableSaveIfRows();
            });
        }); }

        if(sClear && sRows){ on(sClear, 'click', function(){
            sRows.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#777;">No users loaded.</td></tr>';
            enableSaveIfRows();
        }); }

        if(sSave && sRows){ on(sSave, 'click', function(){
            var payload = [];
            var trs = sRows.querySelectorAll('tr[data-uid]');
            trs.forEach(function(tr){
                var uid = parseInt(tr.getAttribute('data-uid'),10);
                if(!uid) return;
                var force = (tr.querySelector('input.aaa-ac-force')||{}).value || '';
                var popup = (tr.querySelector('input.aaa-ac-popup')||{}).value || '';
                var inc   = (tr.querySelector('input.aaa-ac-inc')||{}).checked || false;
                payload.push({ user_id: uid, force: force.trim(), popup: popup.trim(), include: inc });
            });
            sSave.disabled = true;
            var fd = new FormData();
            fd.append('action','aaa_ac_save_user_settings_v2');
            fd.append('nonce', AAA_AC.nonce);
            fd.append('users', JSON.stringify(payload));
            fetch(AAA_AC.ajax,{method:'POST', body: fd, credentials:'same-origin'})
                .then(function(r){ return r.json(); }).then(function(res){
                    sSave.disabled = false;
                    if(res && res.success){
                        alert('Saved ' + (res.data && res.data.saved ? res.data.saved : 0) + ' users.');
                    } else {
                        alert('Save failed.');
                    }
                }).catch(function(){
                    sSave.disabled = false;
                    alert('Error saving.');
                });
        }); }
    });
})();