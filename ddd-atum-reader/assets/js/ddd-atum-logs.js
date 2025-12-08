/* File: plugins/ddd-atum-reader/assets/js/ddd-atum-logs.js
   Purpose: Initialize DataTable; add sticky header, multi-checkbox event filter, user filter, date range filter,
            CSV/Excel/Print export, column visibility, numeric sorting for stock/qty/movement, sticky preferences.
   Dependencies: jQuery; DataTables core; fixedHeader; buttons (html5, print, colVis); localized DDD_ATUM_READER_DATA.user_id
   Needed by: Admin ATUM Logs page
*/
(function($){
  var NS = '[DDD_ATUM_LOGS]';
  function log(){ try{ console.log.apply(console, [NS].concat([].slice.call(arguments))); } catch(e){} }
  function warn(){ try{ console.warn.apply(console, [NS].concat([].slice.call(arguments))); } catch(e){} }
  function err(){ try{ console.error.apply(console, [NS].concat([].slice.call(arguments))); } catch(e){} }

  $(function(){
    log('Booting…');

    if (typeof $.fn.dataTable === 'undefined') {
      return err('DataTables core is not loaded. Aborting init.');
    }
    if (typeof $.fn.DataTable === 'undefined') {
      return err('DataTables wrapper not found. Aborting init.');
    }

    var userId = (window.DDD_ATUM_READER_DATA && window.DDD_ATUM_READER_DATA.user_id) ? window.DDD_ATUM_READER_DATA.user_id : 0;
    var storeKey = function(k){ return 'ddd_atum_' + userId + '_' + k; };

    var $table = $('#ddd-atum-table');
    if (!$table.length) {
      return warn('No #ddd-atum-table found on page. Nothing to initialize.');
    }

    // Buttons set — guard against missing extensions (colvis especially).
    var hasButtons = !!($.fn.dataTable.Buttons);
    var hasColVis  = hasButtons && !!($.fn.dataTable.ext.buttons) && !!($.fn.dataTable.ext.buttons.colvis);
    log('Buttons?', hasButtons, 'ColVis?', hasColVis);

    // Page length (sticky)
    var initialLen = 500;
    var savedLen = localStorage.getItem(storeKey('pageLength'));
    if (savedLen && /^\d+$/.test(savedLen)) {
      initialLen = parseInt(savedLen, 10);
    }
    log('Initial pageLength =', initialLen);

    // Build DataTables config
    var dtConfig = {
      pageLength: initialLen,
      lengthMenu: [[10, 20, 30, 50, 100, 500], [10, 20, 30, 50, 100, 500]],
      columnDefs: [
        { targets: [6,7,8,9], type: "num" } // Old/New/Qty/Movement numeric sort
      ],
      dom: 'Bfrtip',
      buttons: [],
      fixedHeader: true,
      initComplete: function () {
        var api = this.api();
        log('initComplete fired. Rows:', api.rows().count(), 'Cols:', api.columns().count());

        // Restore column visibility
        try {
          var savedCols = localStorage.getItem(storeKey('colVis'));
          if (savedCols) {
            var vis = JSON.parse(savedCols);
            if (Array.isArray(vis)) {
              vis.forEach(function(v, idx){
                if (typeof v === 'boolean' && idx < api.columns().count()) {
                  api.column(idx).visible(v);
                }
              });
              log('Restored column visibility:', vis);
            }
          }
          api.on('column-visibility.dt', function(){
            var arr = [];
            api.columns().every(function(){ arr.push(this.visible()); });
            localStorage.setItem(storeKey('colVis'), JSON.stringify(arr));
            log('Saved column visibility:', arr);
          });
        } catch(e) { err('Column visibility restore/save failed:', e); }

        // Build Event multi-checkbox filter
        try {
          var $eventWrap = $('#ddd-event-filter');
          if ($eventWrap.length) {
            $eventWrap.html('<div id="ddd-event-checkboxes"></div>');
            var $box = $('#ddd-event-checkboxes');
            var eventSet = {};
            api.column(2).data().each(function(d){ if(d){ eventSet[d]=true; }});
            var events = Object.keys(eventSet).sort();
            log('Distinct events:', events);

            var savedEvents = localStorage.getItem(storeKey('events'));
            var checkedSet = {};
            if (savedEvents) {
              try { (JSON.parse(savedEvents) || []).forEach(function(v){ checkedSet[v]=true; }); } catch(e){}
            }
            events.forEach(function(d){
              var isChecked = savedEvents ? !!checkedSet[d] : true; // default = checked
              $box.append(
                '<label><input type="checkbox" class="ddd-event-check" value="'+$('<div>').text(d).html()+'" '+(isChecked?'checked':'')+'> '+d+'</label>'
              );
            });

            var applyEventFilter = function(){
              var selected = [];
              $('.ddd-event-check:checked').each(function(){ selected.push($(this).val()); });
              localStorage.setItem(storeKey('events'), JSON.stringify(selected));
              if (selected.length) {
                var regex = selected.map(function(s){ return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }).join('|');
                api.column(2).search('^(' + regex + ')$', true, false).draw();
                log('Event filter applied:', selected);
              } else {
                api.column(2).search('').draw();
                log('Event filter cleared (show all).');
              }
            };
            $(document).off('change.dddEvent').on('change.dddEvent','.ddd-event-check', applyEventFilter);
            applyEventFilter();
          } else {
            warn('#ddd-event-filter not found; skipping event filter UI.');
          }
        } catch(e) { err('Event filter init failed:', e); }

        // User dropdown filter (use data-user)
        try {
          var $userWrap = $('#ddd-user-filter');
          if ($userWrap.length) {
            var $userSelect = $('<select><option value="">Filter by User</option></select>').appendTo($userWrap);
            $userSelect.on('change', function(){
              var val = $(this).val() || '';
              localStorage.setItem(storeKey('user'), val);
              api.column(3).search(val).draw();
              log('User filter applied:', val);
            });
            var users = {};
            api.rows().every(function(){
              var name = $(this.node()).find('td[data-user]').data('user');
              if (name && !users[name]) {
                users[name] = true;
                $userSelect.append('<option value="'+$('<div>').text(name).html()+'">'+name+'</option>');
              }
            });
            var savedUser = localStorage.getItem(storeKey('user'));
            if (savedUser) {
              $userSelect.val(savedUser);
              api.column(3).search(savedUser).draw();
              log('User filter restored:', savedUser);
            }
          } else {
            warn('#ddd-user-filter not found; skipping user filter UI.');
          }
        } catch(e) { err('User filter init failed:', e); }

        // Date range filter uses data-ts on Date/Time col
        try {
          var $from = $('#ddd-date-from'), $to = $('#ddd-date-to');
          if ($from.length && $to.length) {
            var savedFrom = localStorage.getItem(storeKey('date_from')) || '';
            var savedTo   = localStorage.getItem(storeKey('date_to'))   || '';
            $from.val(savedFrom);
            $to.val(savedTo);

            // push filter only once
            if (!$.fn.dataTable.ext.dddDatePushed) {
              $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
                if (settings.nTable !== $table.get(0)) return true;
                var node = table.row(dataIndex).node();
                var ts = parseInt($(node).find('td[data-ts]').data('ts'), 10);
                if (!ts) return true;
                var fromVal = $from.val();
                var toVal   = $to.val();
                if (!fromVal && !toVal) return true;
                var pass = true;
                if (fromVal) {
                  var fromTs = Date.parse(fromVal + 'T00:00:00Z')/1000;
                  if (ts < fromTs) pass = false;
                }
                if (toVal) {
                  var toTs = Date.parse(toVal + 'T23:59:59Z')/1000;
                  if (ts > toTs) pass = false;
                }
                return pass;
              });
              $.fn.dataTable.ext.dddDatePushed = true;
              log('Date filter function registered.');
            }

            var applyDate = function(){
              var fv = $from.val()||'';
              var tv = $to.val()||'';
              localStorage.setItem(storeKey('date_from'), fv);
              localStorage.setItem(storeKey('date_to'),   tv);
              api.draw();
              log('Date filter applied:', fv, '→', tv);
            };
            $from.off('change.dddDate').on('change.dddDate', applyDate);
            $to.off('change.dddDate').on('change.dddDate', applyDate);

            // Apply saved on load
            if (savedFrom || savedTo) {
              api.draw();
              log('Date filter restored:', savedFrom, '→', savedTo);
            }
          } else {
            warn('Date inputs not found; skipping date filter.');
          }
        } catch(e) { err('Date filter init failed:', e); }

        // Sticky page length
        try {
          table.on('length.dt', function(e, settings, len){
            localStorage.setItem(storeKey('pageLength'), String(len));
            log('Saved pageLength:', len);
          });
        } catch(e) { err('length.dt binding failed:', e); }
      }
    };

    // Build buttons only if available
    if (hasButtons) {
      dtConfig.buttons.push(
        { extend: 'csvHtml5',   text: 'Export CSV',   exportOptions: { columns: ':visible' } },
        { extend: 'excelHtml5', text: 'Export Excel', exportOptions: { columns: ':visible' } },
        { extend: 'print',      text: 'Print' }
      );
      if (hasColVis) {
        dtConfig.buttons.push({ extend: 'colvis', text: 'Columns' });
      } else {
        warn('ColVis extension not detected; hiding "Columns" button.');
      }
    } else {
      warn('DataTables Buttons not detected; export/colvis buttons disabled.');
    }

    // Initialize
    try {
      log('Initializing DataTable with config:', dtConfig);
      var table = $table.DataTable(dtConfig);
      log('DataTable initialized.');
    } catch(e) {
      return err('DataTable init failed:', e);
    }
  });
})(jQuery);
