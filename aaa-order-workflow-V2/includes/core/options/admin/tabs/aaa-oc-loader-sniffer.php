<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/admin/tabs/aaa-oc-loader-sniffer.php
 * Purpose: Loader Sniffer settings tab + report. Optional board widget injector (guarded).
 * Version: 1.2.2
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ---- Capability (mirror parent page) ---- */
$cap = defined('AAA_OC_REQUIRED_CAP') ? AAA_OC_REQUIRED_CAP : 'manage_woocommerce';
if ( ! current_user_can( $cap ) ) { echo '<p>'.esc_html__('You do not have permission to view this tab.','aaa-oc').'</p>'; return; }

/* ---- Ensure options helpers are available (we are already under /core/options/admin/tabs) ---- */
if ( ! function_exists( 'aaa_oc_get_option' ) ) {
	require_once dirname( __DIR__, 2 ) . '/class-aaa-oc-options.php';
	if ( class_exists( 'AAA_OC_Options' ) ) { AAA_OC_Options::init(); }
}

/* ---- Save: widget toggle + fallback default ---- */
$save_action = 'aaa_oc_loader_sniffer_save';
if ( isset($_POST['aaa_oc_sniffer_save'], $_POST['_wpnonce']) && wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['_wpnonce']) ), $save_action ) ) {
	$enable = ! empty( $_POST['enable_sniffer_widget'] ) ? 1 : 0;
	$fb_def = ! empty( $_POST['sniffer_fallback_default'] ) ? 1 : 0;
	aaa_oc_set_option( 'sniffer_widget_on_board',  $enable, 'debug' );
	aaa_oc_set_option( 'sniffer_fallback_default', $fb_def, 'debug' );
	echo '<div class="notice notice-success"><p>'.esc_html__('Settings saved.','aaa-oc').'</p></div>';
}

/* ---- Current settings ---- */
$sniffer_enabled   = (int) aaa_oc_get_option( 'sniffer_widget_on_board',  'debug', 0 );
$fallback_default  = (int) aaa_oc_get_option( 'sniffer_fallback_default', 'debug', 0 );

/* ---- Refresh report (this page request) ---- */
$refresh_action = 'aaa_oc_loader_sniffer_refresh';
$did_submit     = ( isset($_POST['aaa_oc_sniffer_refresh'], $_POST['_wpnonce']) && wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['_wpnonce']) ), $refresh_action ) );
$show_fallback  = isset($_POST['show_fallback']) ? (bool) $_POST['show_fallback'] : (bool) $fallback_default;

/* ---- Compose payload (mirrors board widget) ---- */
$plugin_root = WP_CONTENT_DIR . '/plugins/aaa-order-workflow';
$tracker     = ( isset( $GLOBALS['AAA_OC_LOADER_TRACKER'] ) && is_array( $GLOBALS['AAA_OC_LOADER_TRACKER'] ) )
	? $GLOBALS['AAA_OC_LOADER_TRACKER']
	: [ 'attempts' => [], 'note' => 'Tracker not initialized; add _track_load() in AAA_OC_Loader_Util::require_or_log().' ];

$fallback_files = [];
if ( $show_fallback ) {
	foreach ( get_included_files() as $f ) {
		if ( strpos( $f, $plugin_root ) !== false ) $fallback_files[] = $f;
	}
	sort( $fallback_files );
}

$payload = [
	'meta'    => [
		'now'         => current_time( 'mysql' ),
		'plugin_root' => $plugin_root,
		'screen'      => ( function_exists('get_current_screen') && is_object( get_current_screen() ) ) ? get_current_screen()->id : '',
		'refreshed'   => (bool) $did_submit,
	],
	'tracker' => $tracker,
];
if ( $show_fallback ) $payload['fallback_included_files'] = $fallback_files;

/* ---- Render settings + report ---- */
echo '<div class="wrap">';
echo '<h2 style="margin-top:0;">'.esc_html__('Loader Sniffer','aaa-oc').'</h2>';
echo '<p>'.esc_html__('Shows files and loader attempts for this request. Enable fallback to list all included files under this plugin.','aaa-oc').'</p>';

/* Toggle form */
echo '<h3 style="margin-top:18px;">'.esc_html__('Board Widget','aaa-oc').'</h3>';
echo '<form method="post" style="margin:8px 0 18px;">'; wp_nonce_field( $save_action );
echo '<label><input type="checkbox" name="enable_sniffer_widget" value="1" '.checked(1,$sniffer_enabled,false).'> '.esc_html__('Show Sniffer on Workflow Board','aaa-oc').'</label><br>';
echo '<label><input type="checkbox" name="sniffer_fallback_default" value="1" '.checked(1,$fallback_default,false).'> '.esc_html__('Also include fallback (get_included_files) by default','aaa-oc').'</label> ';
echo '<button type="submit" name="aaa_oc_sniffer_save" class="button button-primary" style="margin-left:8px;">'.esc_html__('Save','aaa-oc').'</button>';
echo '</form>';

/* Report form */
echo '<h3 style="margin-top:18px;">'.esc_html__('Loader Report (this page)','aaa-oc').'</h3>';
echo '<form method="post" style="margin:12px 0;">'; wp_nonce_field( $refresh_action );
echo '<label><input type="checkbox" name="show_fallback" value="1" '.checked($show_fallback,true,false).'> '.esc_html__('Also show fallback (get_included_files under plugin)','aaa-oc').'</label> ';
echo '<button type="submit" name="aaa_oc_sniffer_refresh" class="button" style="margin-left:8px;">'.esc_html__('Refresh Loader Report','aaa-oc').'</button> ';
echo '<button type="button" id="aaa-oc-copy-json" class="button">'.esc_html__('Copy JSON','aaa-oc').'</button>';
echo '</form>';

/* Pretty JSON outputs */
$pretty_now  = esc_html( wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
echo '<pre id="aaa-oc-sniff-output" style="white-space:pre-wrap;background:#fff;padding:12px;border:1px solid #ddd;max-height:60vh;overflow:auto;">'.$pretty_now.'</pre>';

echo '<details style="margin-top:16px;border:1px solid #ddd;background:#fff;border-radius:6px;">';
echo '<summary style="cursor:pointer;padding:8px 10px;font-weight:600;">'.esc_html__('Board Widget Preview (collapsible)','aaa-oc').'</summary>';
echo '<div style="padding:10px;"><p style="margin-top:0">'.esc_html__('Same payload the board widget renders.','aaa-oc').'</p>';
echo '<p><button type="button" id="aaa-oc-copy-json-preview" class="button">'.esc_html__('Copy JSON','aaa-oc').'</button></p>';
echo '<pre id="aaa-oc-sniff-preview" style="white-space:pre-wrap;background:#fff;padding:10px;border:1px solid #eee;max-height:280px;overflow:auto;">'.$pretty_now.'</pre>';
echo '</div></details>';
echo '</div>';

/* Footer JS for copy buttons */
add_action( 'admin_print_footer_scripts', function () {
	?>
	<script>
	(function(){
	  function copySel(id){
	    try{ const el=document.getElementById(id); if(!el) return alert('Output not found.');
	      const t=el.textContent||''; navigator.clipboard.writeText(t).then(function(){ alert('Copied JSON to clipboard.'); });
	    }catch(e){ alert('Copy failed'); }
	  }
	  var a=document.getElementById('aaa-oc-copy-json'), b=document.getElementById('aaa-oc-copy-json-preview');
	  if(a) a.addEventListener('click', function(){ copySel('aaa-oc-sniff-output'); });
	  if(b) b.addEventListener('click', function(){ copySel('aaa-oc-sniff-preview'); });
	})();
	</script>
	<?php
}, 99 );

/* ---------------------------------------------------------------------------
 * Board widget injector (guarded): only injects if board hook did NOT already do so.
 * ------------------------------------------------------------------------- */
add_action( 'admin_enqueue_scripts', function () use ( $payload, $sniffer_enabled ) {

	// If the board hook already injected for this request, skip this injector.
	if ( defined( 'AAA_OC_SNIFFER_WIDGET_READY' ) && AAA_OC_SNIFFER_WIDGET_READY ) {
		return;
	}

	// Honor toggle + page scope
	if ( ! $sniffer_enabled ) return;
	$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
	if ( $page !== 'aaa-oc-workflow-board' ) return;

	// Fallback/simple widget (compact box)
	wp_register_script( 'aaa-oc-sniffer-widget', false, [ 'jquery' ], '1.1.1', true );
	wp_localize_script( 'aaa-oc-sniffer-widget', 'AAA_OC_SNIFFER', [
		'payload' => $payload,
		'i18n'    => [ 'title' => __( 'Loader Sniffer (this request)', 'aaa-oc' ) ],
	] );
	$inline = <<<JS
(function($){
  'use strict';
  function findAnchor(){ var sel=['#aaa-oc-actions-body','#aaa-oc-toolbar','#wpbody-content','body']; for(var i=0;i<sel.length;i++){ var $a=$(sel[i]); if($a.length) return $a; } return $('body'); }
  function ensurePanel(){
    if($('#aaa-oc-sniffer-box').length) return true;
    var $body=findAnchor();
    var html=['<section id="aaa-oc-sniffer-box" style="margin-top:10px;">',
      '<h3 style="margin:.2em 0 .4em;font-size:14px;">'+(AAA_OC_SNIFFER.i18n&&AAA_OC_SNIFFER.i18n.title?AAA_OC_SNIFFER.i18n.title:'Loader Sniffer')+'</h3>',
      '<pre style="white-space:pre-wrap;background:#fff;padding:8px;border:1px solid #ddd;max-height:260px;overflow:auto;"></pre>',
    '</section>'].join('');
    $body.append(html);
    try{ $('#aaa-oc-sniffer-box pre').text(JSON.stringify(AAA_OC_SNIFFER.payload||{}, null, 2)); }catch(e){}
  }
  $(function(){ ensurePanel(); });
  $(document).on('aaa-oc:toolbar:ready', ensurePanel);
})(jQuery);
JS;
	wp_add_inline_script( 'aaa-oc-sniffer-widget', $inline, 'after' );
	wp_enqueue_script( 'aaa-oc-sniffer-widget' );

	if ( defined('WP_DEBUG') && WP_DEBUG ) { error_log('[AAA-OC][SNIFFER] Settings-tab injector used (guard path)'); }
}, 10 );
