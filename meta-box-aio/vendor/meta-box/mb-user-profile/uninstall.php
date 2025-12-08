<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'mbup_keys' );
delete_option( 'mbup_db_version' );