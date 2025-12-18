<?php
/**
 * Plugin Name: Meta Box AIO
 * Plugin URI:  https://metabox.io/pricing/
 * Description: All Meta Box extensions in one package.
 * Version:     3.3.5
 * Author:      MetaBox.io
 * Author URI:  https://metabox.io
 * License:     GPL2+
 * Text Domain: meta-box-aio
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

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Use 'plugins_loaded' hook to make sure it runs "after" individual extensions are loaded.
// So individual extensions can take a higher priority.
add_action( 'plugins_loaded', function (): void {
	require __DIR__ . '/vendor/autoload.php';
} );

define( 'META_BOX_AIO_DIR', __DIR__ );
define( 'META_BOX_AIO_URL', plugin_dir_url( __FILE__ ) );

require __DIR__ . '/src/Loader.php';
require __DIR__ . '/src/Settings.php';
require __DIR__ . '/vendor/meta-box/dependency/Plugins.php';

new MBAIO\Loader;
new MBAIO\Settings;

if ( is_admin() ) {
	require __DIR__ . '/src/Tools.php';
	new MBAIO\Tools;
}

// Load translations
add_action( 'init', function (): void {
	load_plugin_textdomain( 'meta-box-aio', false, basename( __DIR__ ) . '/languages/meta-box-aio' );
	load_plugin_textdomain( 'meta-box', false, basename( __DIR__ ) . '/languages/meta-box' );
	load_plugin_textdomain( 'mb-custom-post-type', false, basename( __DIR__ ) . '/languages/mb-custom-post-type' );
} );
