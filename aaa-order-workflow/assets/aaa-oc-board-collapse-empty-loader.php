<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/assets/aaa-oc-board-collapse-empty-loader.php
 * Purpose: Enqueue the "Collapse If Empty" JS on the Workflow Board admin page.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
const DEBUG_THIS_FILE = false;

add_action( 'admin_enqueue_scripts', function () {
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if ( empty( $screen->id ) ) { return; }

	// Only load on the Workflow Board page
	$on_board = isset($_GET['page']) && $_GET['page'] === 'aaa-oc-workflow-board'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( strpos( $screen->id, 'aaa-oc' ) === false || ! $on_board ) { return; }

	if ( DEBUG_THIS_FILE ) error_log('[AAA-OC][CollapseEmptyLoader] enqueue on ' . $screen->id);

	wp_enqueue_script(
		'aaa-oc-board-collapse-empty',
		plugins_url( '../../assets/js/board-collapse-empty.js', __FILE__ ),
		array( 'jquery' ),
		defined('AAA_OC_VERSION') ? AAA_OC_VERSION : '1.0.0',
		true
	);
});
