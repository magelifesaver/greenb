<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/admin/tabs/popup-reports.php
 * Purpose: Popup Reports UI for aaa_ac_popup_logs
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$roles_map = function_exists('aaa_ac_get_all_roles') ? aaa_ac_get_all_roles() : [];
?>
<h2><?php esc_html_e('Popup Reports','aaa-ac'); ?></h2>
<p><?php esc_html_e('Filter by role, date range, and action. Click "Load Records" to refresh.','aaa-ac'); ?></p>

<div class="aaa-ac-toolbar">
	<label for="aaa_ac_popup_reports_role"><?php esc_html_e('Role','aaa-ac'); ?></label>
	<select id="aaa_ac_popup_reports_role" style="min-width:200px;">
		<option value=""><?php esc_html_e('All Roles','aaa-ac'); ?></option>
		<?php foreach($roles_map as $slug=>$label): ?>
			<option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
		<?php endforeach; ?>
	</select>

	<label for="aaa_ac_popup_reports_start"><?php esc_html_e('From','aaa-ac'); ?></label>
	<input type="date" id="aaa_ac_popup_reports_start">
	<label for="aaa_ac_popup_reports_end"><?php esc_html_e('To','aaa-ac'); ?></label>
	<input type="date" id="aaa_ac_popup_reports_end">

	<label for="aaa_ac_popup_reports_action"><?php esc_html_e('Action','aaa-ac'); ?></label>
	<select id="aaa_ac_popup_reports_action">
		<option value=""><?php esc_html_e('All','aaa-ac'); ?></option>
		<option value="shown"><?php esc_html_e('Shown','aaa-ac'); ?></option>
		<option value="confirmed"><?php esc_html_e('Confirmed','aaa-ac'); ?></option>
		<option value="switch"><?php esc_html_e('Switch','aaa-ac'); ?></option>
	</select>

	<label for="aaa_ac_popup_reports_per" style="margin-left:12px;"><?php esc_html_e('Per Page','aaa-ac'); ?></label>
	<select id="aaa_ac_popup_reports_per">
		<option>25</option>
		<option selected>50</option>
		<option>100</option>
		<option>200</option>
	</select>

	<button class="button" id="aaa_ac_popup_reports_load"><?php esc_html_e('Load Records','aaa-ac'); ?></button>
	<button class="button button-secondary" id="aaa_ac_popup_reports_export"><?php esc_html_e('Export CSV','aaa-ac'); ?></button>

	<span style="margin-left:auto;color:#666;">
		<span id="aaa_ac_popup_reports_count">0</span>
		&nbsp;&nbsp;
		<button class="button" id="aaa_ac_popup_reports_prev" disabled>&laquo; <?php esc_html_e('Prev','aaa-ac'); ?></button>
		<span id="aaa_ac_popup_reports_page">1 / 1</span>
		<button class="button" id="aaa_ac_popup_reports_next" disabled><?php esc_html_e('Next','aaa-ac'); ?> &raquo;</button>
	</span>
</div>

<table class="widefat striped" id="aaa_ac_popup_reports" style="margin-top:12px;">
	<thead>
	<tr>
		<th data-sort="id"><?php esc_html_e('Log ID','aaa-ac'); ?></th>
		<th data-sort="user_id"><?php esc_html_e('User ID','aaa-ac'); ?></th>
		<th><?php esc_html_e('User','aaa-ac'); ?></th>
		<th><?php esc_html_e('Role(s)','aaa-ac'); ?></th>
		<th data-sort="due_hhmm"><?php esc_html_e('Due Time','aaa-ac'); ?></th>
		<th data-sort="shown_at"><?php esc_html_e('Shown At','aaa-ac'); ?></th>
		<th data-sort="action"><?php esc_html_e('Action','aaa-ac'); ?></th>
		<th data-sort="handled_at"><?php esc_html_e('Handled At','aaa-ac'); ?></th>
		<th><?php esc_html_e('Token','aaa-ac'); ?></th>
		<th data-sort="site_id"><?php esc_html_e('Site ID','aaa-ac'); ?></th>
		<th><?php esc_html_e('Admin Page','aaa-ac'); ?></th>
	</tr>
	</thead>
	<tbody id="aaa_ac_popup_reports_rows">
	<tr><td colspan="11" style="text-align:center;color:#777;"><?php esc_html_e('No records. Click Load.','aaa-ac'); ?></td></tr>
	</tbody>
</table>

<script>
(function(){
  // Uses same pattern as reports.js, kept inline for this tab if you don't have popup-reports.js
  var ajax = "<?php echo esc_js( admin_url('admin-ajax.php') ); ?>";
  var nonce = "<?php echo esc_js( wp_create_nonce('aaa_ac_ajax') ); ?>";
  function qs(id){ return document.getElementById(id); }
  var role=qs('aaa_ac_popup_reports_role'),
      start=qs('aaa_ac_popup_reports_start'),
      end=qs('aaa_ac_popup_reports_end'),
      act=qs('aaa_ac_popup_reports_action'),
      per=qs('aaa_ac_popup_reports_per'),
      load=qs('aaa_ac_popup_reports_load'),
      exp=qs('aaa_ac_popup_reports_export'),
      rows=qs('aaa_ac_popup_reports_rows'),
      prev=qs('aaa_ac_popup_reports_prev'),
      next=qs('aaa_ac_popup_reports_next'),
      pageEl=qs('aaa_ac_popup_reports_page'),
      count=qs('aaa_ac_popup_reports_count');

  var state = { sort:'id', dir:'DESC', page:1, per: (per?parseInt(per.value,10):50) || 50 };

  function bindSort(){
    var ths = document.querySelectorAll('#aaa_ac_popup_reports thead th[data-sort]');
    ths.forEach(function(th){
      th.addEventListener('click', function(){
        var k = th.getAttribute('data-sort');
        if(!k) return;
        if(state.sort===k){ state.dir = (state.dir==='ASC'?'DESC':'ASC'); } else { state.sort=k; state.dir='ASC'; }
        loadPage(1);
      });
    });
  }

  function loadPage(p){
    state.page = p || 1;
    var url = ajax
      + '?action=aaa_ac_popup_reports_load'
      + '&nonce=' + encodeURIComponent(nonce)
      + '&role=' + encodeURIComponent(role && role.value ? role.value : '')
      + '&start=' + encodeURIComponent(start && start.value ? start.value : '')
      + '&end=' + encodeURIComponent(end && end.value ? end.value : '')
      + '&act=' + encodeURIComponent(act && act.value ? act.value : '')
      + '&sort=' + encodeURIComponent(state.sort)
      + '&dir=' + encodeURIComponent(state.dir)
      + '&page=' + encodeURIComponent(state.page)
      + '&per=' + encodeURIComponent(state.per);

    rows.innerHTML = '<tr><td colspan="11" style="text-align:center;">Loading…</td></tr>';
    fetch(url, {credentials:'same-origin'}).then(r=>r.json()).then(function(res){
      if(res && res.success){
        rows.innerHTML = res.data.rows || '<tr><td colspan="11" style="text-align:center;color:#777;">No records.</td></tr>';
        if(count) count.textContent = (res.data.total||0) + ' records';
        if(pageEl) pageEl.textContent = (res.data.page||1) + ' / ' + (res.data.total_pages||1);
        if(prev) prev.disabled = (state.page<=1);
        if(next) next.disabled = (res.data.page>=res.data.total_pages);
      }else{
        rows.innerHTML = '<tr><td colspan="11">Failed to load.</td></tr>';
      }
    }).catch(function(){
      rows.innerHTML = '<tr><td colspan="11">Error.</td></tr>';
    });
  }

  if(load){ load.addEventListener('click', function(){ loadPage(1); }); }
  if(prev){ prev.addEventListener('click', function(){ if(state.page>1) loadPage(state.page-1); }); }
  if(next){ next.addEventListener('click', function(){ loadPage(state.page+1); }); }
  if(per){ per.addEventListener('change', function(){ state.per = parseInt(per.value||'50',10)||50; loadPage(1); }); }
  if(exp){
    exp.addEventListener('click', function(){
      var url = ajax
        + '?action=aaa_ac_popup_reports_export'
        + '&nonce=' + encodeURIComponent(nonce)
        + '&role=' + encodeURIComponent(role && role.value ? role.value : '')
        + '&start=' + encodeURIComponent(start && start.value ? start.value : '')
        + '&end=' + encodeURIComponent(end && end.value ? end.value : '')
        + '&act=' + encodeURIComponent(act && act.value ? act.value : '');
      window.location.href = url;
    });
  }

  bindSort();
  loadPage(1);
})();
</script>
