<?php
/**
 * Plugin Name:       MB Views
 * Plugin URI:        https://metabox.io/plugins/mb-views/
 * Description:       Create views for Meta Box fields and content.
 * Version:           1.14.0
 * Requires at least: 6.2
 * Requires PHP:      7.2.5
 * Author:            MetaBox.io
 * Author URI:        https://metabox.io
 * License:           GPL2+
 * Text Domain:       mb-views
 * Domain Path:       /languages/
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

if ( ! function_exists( 'mb_views_load' ) ) {
	if ( file_exists( __DIR__ . '/vendor' ) ) {
		require __DIR__ . '/vendor/autoload.php';
		require __DIR__ . '/vendor/meta-box/meta-box-conditional-logic/meta-box-conditional-logic.php';
		require __DIR__ . '/vendor/meta-box/meta-box-tooltip/meta-box-tooltip.php';
	}

	add_action( 'init', 'mb_views_load', 5 );

	function mb_views_load() {
		if ( ! defined( 'RWMB_VER' ) ) {
			return;
		}

		require __DIR__ . '/bootstrap.php';

		// Require conditional logic and tooltip in AIO.
		if ( class_exists( 'MetaBox\Dependency\Plugins' ) ) {
			new MetaBox\Dependency\Plugins(
				'MB Views',
				[
					[
						'name'  => 'Meta Box Conditional Logic',
						'class' => 'MB_Conditional_Logic',
					],
					[
						'name'  => 'Meta Box Tooltip',
						'class' => 'MB_Tooltip',
					],
				],
				[
					// Translators: %1$s - the plugin name, %2$s - extensions, %3$s - action.
					'message'  => __( '%1$s requires %2$s to function correctly. %3$s.', 'mb-views' ),
					'activate' => __( 'Activate now', 'mb-views' ),
				]
			);
		}
	}
}
