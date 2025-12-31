<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/announcements/admin/tabs/aaa-oc-annc.php
 * Purpose: Lightweight tab that links to the full Announcements Manager page.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$settings_slug = 'aaa-oc-announcements';
$url = admin_url( 'admin.php?page=' . $settings_slug );
?>
<div class="wrap">
	<h2><?php esc_html_e( 'Announcements', 'aaa-oc' ); ?></h2>
	<p><?php esc_html_e( 'Create, schedule, and manage Workflow Board announcements. Use the manager below.', 'aaa-oc' ); ?></p>
	<p><a href="<?php echo esc_url( $url ); ?>" class="button button-primary"><?php esc_html_e( 'Open Announcements Manager', 'aaa-oc' ); ?></a></p>
</div>
