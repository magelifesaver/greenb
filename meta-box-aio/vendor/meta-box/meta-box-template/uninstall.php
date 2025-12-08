<?php
// If uninstall not called from WordPress, then exit.
if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	delete_option( 'meta_box_template' );
}
