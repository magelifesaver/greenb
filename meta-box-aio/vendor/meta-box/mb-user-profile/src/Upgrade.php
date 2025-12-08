<?php
namespace MetaBox\UserProfile;

class Upgrade {
	public function __construct() {
		$db_version = (int) get_option( 'mbup_db_version', 1 );
		if ( $db_version >= MBUP_DB_VER ) {
			return;
		}

		delete_option( 'mbup_keys' );

		// Always update the DB version to the plugin version.
		update_option( 'mbup_db_version', MBUP_DB_VER );
	}
}
