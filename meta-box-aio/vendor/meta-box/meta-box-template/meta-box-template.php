<?php
/**
 * Plugin Name: MB Template
 * Plugin URI:  http://metabox.io/plugins/meta-box-template/
 * Description: Configure field groups easily with YAML templates.
 * Version:     1.2.4
 * Author:      MetaBox.io
 * Author URI:  https://metabox.io
 * License:     GPL2+
 * Text Domain: meta-box-template
 * Domain Path: /languages/
 *
 * Copyright (C) 2010-2025 Tran Ngoc Tuan Anh. All rights reserved.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// Prevent loading this file directly
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! function_exists( 'mb_template_load' ) ) {
	if ( file_exists( __DIR__ . '/vendor' ) ) {
		require __DIR__ . '/vendor/autoload.php';
	}

	add_action( 'init', 'mb_template_load', 0 );

	function mb_template_load() {
		if ( ! defined( 'RWMB_VER' ) ) {
			return;
		}

		list( , $url ) = RWMB_Loader::get_path( __DIR__ );
		define( 'MB_TEMPLATE_DIR', __DIR__ );
		define( 'MB_TEMPLATE_URL', $url );

		$parser = new MBTemplate\Parser;

		if ( is_admin() ) {
			new MBTemplate\Settings( $parser );
		}
		new MBTemplate\Register( $parser );

		load_plugin_textdomain( 'meta-box-template', false, plugin_basename( __DIR__ ) . '/languages/' );
	}
}
