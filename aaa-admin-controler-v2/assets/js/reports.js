/* File: /wp-content/plugins/aaa-admin-controler-v2/assets/js/reports.js
   Purpose: Reports tab (filters, sort, pagination, CSV export) */
(function(){
  var NS='[AAA_AC_REPORTS]';
  function qs(id){ return document.getElementById(id); }
  function on(el,ev,fn){ if(el) el.addEventListener(ev,fn); }
  function log(){ try{ console.log.apply(console, arguments);}catch(e){} }

  document.addEventListener('DOMContentLoaded', function(){
    var role   = qs('aaa_ac_reports_role');
    var start  = qs('aaa_ac_reports_start');
    var end    = qs('aaa_ac_reports_end');
    var load   = qs('aaa_ac_reports_load');
    var exportBtn = qs('aaa_ac_reports_export');
    var rows   = qs('aaa_ac_reports_rows');
    var count  = qs('aaa_ac_reports_count');
    var prev   = qs('aaa_ac_reports_prev');
    var next   = qs('aaa_ac_reports_next');
    var pageEl = qs('aaa_ac_reports_page');
    var perSel = qs('aaa_ac_reports_per');

    if(!rows) return;

    var state = {
      sort: 'login_time',
      dir: 'DESC',
      page: 1,
      per: 50
    };

    function bindSort(){
      var ths = document.querySelectorAll('#aaa_ac_reports thead th[data-sort]');
      ths.forEach(function(th){
        on(th,'click', function(){
          var k = th.getAttribute('data-sort');
          if(!k) return;
          if(state.sort===k){ state.dir = (state.dir==='ASC'?'DESC':'ASC'); } else { state.sort = k; state.dir='ASC'; }
          loadPage(1);
        });
      });
    }

    function loadPage(p){
      state.page = p || 1;
      var url = AAA_AC.ajax
        + '?action=aaa_ac_reports_load'
        + '&nonce=' + encodeURIComponent(AAA_AC.nonce)
        + '&role=' + encodeURIComponent(role && role.value ? role.value : '')
        + '&start=' + encodeURIComponent(start && start.value ? start.value : '')
        + '&end=' + encodeURIComponent(end && end.value ? end.value : '')
        + '&sort=' + encodeURIComponent(state.sort)
        + '&dir=' + encodeURIComponent(state.dir)
        + '&page=' + encodeURIComponent(state.page)
        + '&per=' + encodeURIComponent(state.per);

      log(NS,'LOAD', url);
      rows.innerHTML = '<tr><td colspan="10" style="text-align:center;">Loading…</td></tr>';

      fetch(url, {credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(res){
          log(NS,'JSON', res);
          if(res && res.success){
            rows.innerHTML = res.data.rows || '<tr><td colspan="10" style="text-align:center;color:#777;">No records.</td></tr>';
            if(count) count.textContent = (res.data.total||0) + ' records';
            if(pageEl) pageEl.textContent = (res.data.page||1) + ' / ' + (res.data.total_pages||1);
            if(prev) prev.disabled = (state.page<=1);
            if(next) next.disabled = (res.data.page>=res.data.total_pages);
          }else{
            rows.innerHTML = '<tr><td colspan="10">Failed to load.</td></tr>';
          }
        })
        .catch(function(e){
          console.error(NS,'error', e);
          rows.innerHTML = '<tr><td colspan="10">Error.</td></tr>';
        });
    }

    // Filters
    if(load){ on(load,'click', function(){ loadPage(1); }); }
    if(prev){ on(prev,'click', function(){ if(state.page>1) loadPage(state.page-1); }); }
    if(next){ on(next,'click', function(){ loadPage(state.page+1); }); }
    if(perSel){ on(perSel,'change', function(){ state.per = parseInt(perSel.value||'50',10)||50; loadPage(1); }); }

    if(exportBtn){
      on(exportBtn,'click', function(){
        var url = AAA_AC.ajax
          + '?action=aaa_ac_reports_export'
          + '&nonce=' + encodeURIComponent(AAA_AC.nonce)
          + '&role=' + encodeURIComponent(role && role.value ? role.value : '')
          + '&start=' + encodeURIComponent(start && start.value ? start.value : '')
          + '&end=' + encodeURIComponent(end && end.value ? end.value : '')
          + '&sort=' + encodeURIComponent(state.sort)
          + '&dir=' + encodeURIComponent(state.dir);
        log(NS,'EXPORT', url);
        window.location.href = url;
      });
    }

    bindSort();

    // auto-load on open:
    loadPage(1);
  });
})();
