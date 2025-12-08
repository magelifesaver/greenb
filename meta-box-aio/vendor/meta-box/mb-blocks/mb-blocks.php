<?php
/**
 * Plugin Name: MB Blocks
 * Plugin URI:  https://metabox.io/plugins/mb-blocks/
 * Description: Create custom Gutenberg blocks.
 * Version:     1.7.6
 * Author:      MetaBox.io
 * Author URI:  https://metabox.io
 * License:     GPL2+
 * Text Domain: mb-blocks
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

// Prevent loading this file directly.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! function_exists( 'mb_blocks_load' ) ) {

	if ( file_exists( __DIR__ . '/vendor' ) ) {
		require __DIR__ . '/vendor/autoload.php';
	}

	add_action( 'init', 'mb_blocks_load', 5 );

	function mb_blocks_load() {
		if ( ! defined( 'RWMB_VER' ) ) {
			return;
		}

		list( , $url ) = RWMB_Loader::get_path( __DIR__ );
		define( 'MB_BLOCKS_DIR', __DIR__ );
		define( 'MB_BLOCKS_URL', $url );
		define( 'MB_BLOCKS_VER', '1.7.6' );

		new MBBlocks\Loader();
		new MBBlocks\Api();

		load_plugin_textdomain( 'mb-blocks', false, plugin_basename( __DIR__ ) . '/languages/' );
	}
}
