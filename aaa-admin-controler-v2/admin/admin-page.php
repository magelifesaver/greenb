<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/admin/admin-page.php
 * Purpose: Render tabbed Network Admin UI: Settings / Sessions / Reports
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined('AAA_AC_DEBUG_THIS_FILE') ) define('AAA_AC_DEBUG_THIS_FILE', true);

$tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';
$tabs = [
	'settings'      => __('Settings','aaa-ac'),
	'sessions'      => __('Sessions (Realtime)','aaa-ac'),
	'reports'       => __('Reports','aaa-ac'),
	'popup_reports' => __('Popup Reports','aaa-ac'),
];
?>
<div class="wrap" id="aaa-ac-page">
	<h1><?php esc_html_e('Online Users Across the Network','aaa-ac'); ?></h1>

	<h2 class="nav-tab-wrapper">
		<?php foreach($tabs as $k=>$label): ?>
			<a class="nav-tab <?php echo $tab===$k?'nav-tab-active':''; ?>"
			   href="<?php echo esc_url( add_query_arg(['page'=>'aaa-ac-online','tab'=>$k], network_admin_url('admin.php')) ); ?>">
				<?php echo esc_html($label); ?>
			</a>
		<?php endforeach; ?>
	</h2>

	<div class="aaa-ac-tabwrap">
		<?php
		switch($tab){
			case 'sessions': 	require AAA_AC_PATH.'admin/tabs/sessions.php'; break;
			case 'popup_reports': 	require AAA_AC_PATH.'admin/tabs/popup-reports.php'; break;
			case 'reports':  	require AAA_AC_PATH.'admin/tabs/reports.php';  break;
			default:         	require AAA_AC_PATH.'admin/tabs/settings.php'; break;
		}
		?>
	</div>
</div>
