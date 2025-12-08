<?php
namespace MetaBox\UserProfile;

class Helper {
	public static function convert_boolean( $value ): string {
		return $value ? 'true' : 'false';
	}
}
