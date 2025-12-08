<?php
namespace MBViews;

class ActiveView {
	private static $view;

	public static function set_view( $view ) {
		self::$view = $view;
	}

	public static function get_view() {
		return self::$view;
	}
}
