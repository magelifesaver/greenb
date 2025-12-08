<?php
// inc/cron.php
if ( ! defined( 'ABSPATH' ) ) exit;

/** Hourly reconciliation to catch anything missed by events */
add_action( 'aaa_reconcile_stock_event', 'aaa_reconcile_stock_statuses' );
