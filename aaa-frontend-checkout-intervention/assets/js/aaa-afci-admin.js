/* File: /assets/js/aaa-afci-admin.js
 * Purpose: Admin UX for unified Session Log page (expanders, refresh, export).
 * Version: 1.4.0
 */
/* global afciAdmin */

(function($){
  'use strict';

  // Debug helper (no-op when disabled)
  const DEBUG = !!(window.afciAdmin && (parseInt(afciAdmin.debug, 10) === 1));
  function dbg(){ if (DEBUG && window.console && console.debug) { console.debug('[AFCI]', ...arguments); } }

  function ajax(action, data) {
    data = data || {};
    data.action = action;
    data.nonce  = afciAdmin.nonce;
    dbg('AJAX →', action, data);
    return $.post(afciAdmin.ajax, data).always(function(res){
      dbg('AJAX ←', action, res);
    });
  }

  function downloadJSON(filename, obj) {
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(obj, null, 2));
    const a = document.createElement('a');
    a.setAttribute('href', dataStr);
    a.setAttribute('download', filename);
    document.body.appendChild(a);
    a.click();
    a.remove();
    dbg('Download JSON', filename);
  }

  // Refresh sessions
  $('#afci-refresh').on('click', function(){
    const errorsOnly = $('#afci-filter').val() === 'errors';
    dbg('Refresh clicked (errorsOnly=', errorsOnly, ')');
    ajax('afci_list_sessions', { errors_only: errorsOnly ? 1 : 0 }).done(function(res){
      if (res && res.success && res.data && res.data.html) {
        const tbody = $('#afci-sessions-table tbody');
        tbody.html(res.data.html);
      }
    });
  });

  // Filter change triggers refresh
  $('#afci-filter').on('change', function(){
    dbg('Filter changed:', $(this).val());
    $('#afci-refresh').trigger('click');
  });

  // Expand / collapse session → load events once
  $(document).on('click', '.afci-expand', function(){
    const btn = $(this);
    const tr  = btn.closest('tr.afci-session-row');
    const sk  = tr.data('session');
    const exp = $('tr.afci-expander-row[data-session="'+sk+'"]');
    const open = exp.is(':visible');

    dbg('Session expand toggle', { session: sk, open: open });

    if (open) {
      exp.hide();
      btn.text('▸').attr('aria-expanded', 'false');
      return;
    }

    // Open and fetch if needed
    exp.show();
    btn.text('▾').attr('aria-expanded', 'true');
    const body = exp.find('.afci-events-body');

    // If it's still "Loading…" fetch events
    if (body.find('tr').length === 1 && body.find('td').text().match(/Loading/i)) {
      ajax('afci_fetch_session_events', { session_key: sk }).done(function(res){
        if (res && res.success && res.data && res.data.html) {
          body.html(res.data.html);
        } else {
          body.html('<tr><td colspan="8">No events found.</td></tr>');
        }
      });
    }
  });

  // Expand event → load details once
  $(document).on('click', '.afci-event-expand', function(){
    const btn = $(this);
    const tr  = btn.closest('tr.afci-event-row');
    const ev  = tr.data('event');
    const exp = $('tr.afci-event-expander-row[data-event="'+ev+'"]');
    const open = exp.is(':visible');

    dbg('Event expand toggle', { event: ev, open: open });

    if (open) {
      exp.hide();
      btn.text('▸').attr('aria-expanded', 'false');
      return;
    }

    exp.show();
    btn.text('▾').attr('aria-expanded', 'true');

    const box = exp.find('.afci-event-details');
    if (box.text().match(/Loading/i)) {
      ajax('afci_fetch_event_details', { event_id: ev }).done(function(res){
        if (res && res.success && res.data && res.data.html) {
          box.html(res.data.html);
        } else {
          box.html('<p>Unable to load details.</p>');
        }
      });
    }
  });

  // Export session as JSON
  $(document).on('click', '.afci-export-session', function(){
    const sk = $(this).data('session');
    dbg('Export session', sk);
    ajax('afci_export_session', { session_key: sk }).done(function(res){
      if (res && res.success && res.data && res.data.json) {
        downloadJSON('afci-session-'+sk+'.json', res.data.json);
      }
    });
  });

  // Export event as JSON
  $(document).on('click', '.afci-export-event', function(){
    const ev = $(this).data('event');
    dbg('Export event', ev);
    ajax('afci_export_event', { event_id: ev }).done(function(res){
      if (res && res.success && res.data && res.data.json) {
        downloadJSON('afci-event-'+ev+'.json', res.data.json);
      }
    });
  });

})(jQuery);
