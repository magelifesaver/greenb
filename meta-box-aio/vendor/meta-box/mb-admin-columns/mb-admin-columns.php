<?php
/**
 * Plugin Name: MB Admin Columns
 * Plugin URI:  https://metabox.io/plugins/mb-admin-columns/
 * Description: Show custom fields in the post list table.
 * Version:     1.8.0
 * Author:      MetaBox.io
 * Author URI:  https://metabox.io
 * License:     GPL2+
 * Text Domain: mb-admin-columns
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

if ( ! function_exists( 'mb_admin_columns_load' ) ) {

	if ( file_exists( __DIR__ . '/vendor' ) ) {
		require __DIR__ . '/vendor/autoload.php';
	}

	add_action( 'admin_init', 'mb_admin_columns_load' );

	function mb_admin_columns_load() {
		if ( ! defined( 'RWMB_VER' ) ) {
			return;
		}

		$loader = new MBAC\Loader();
		$loader->posts();
		$loader->taxonomies();
		$loader->users();
		$loader->models();
	}
}
