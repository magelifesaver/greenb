<?php
/**
 * Plugin Name: MB Show Hide
 * Plugin URI:  https://metabox.io/plugins/meta-box-show-hide/
 * Description: Easily toggle field groups by various conditions using JavaScript.
 * Version:     1.3.1
 * Author:      MetaBox.io
 * Author URI:  https://metabox.io
 * License:     GPL2+
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

if ( ! class_exists( 'MB_Show_Hide' ) ) {

	/**
	 * This class controls toggling meta boxes via JS
	 * All meta boxes are included, but the job of showing/hiding them are handled via JS
	 */
	class MB_Show_Hide {
		/**
		 * Add hooks when class is loaded
		 */
		public function __construct() {
			add_action( 'rwmb_before', array( $this, 'js_data' ) );
			add_action( 'rwmb_enqueue_scripts', array( $this, 'enqueue' ) );
		}

		/**
		 * Output data for Javascript in data-show, data-hide attributes
		 * Data is output as a .mb-show-hide inside the meta box
		 * JS will read this data and process
		 *
		 * @param RW_Meta_Box $obj The meta box object.
		 */
		public function js_data( RW_Meta_Box $obj ) {
			$meta_box = $obj->meta_box;
			$conditions = array( 'show', 'hide' );
			$data = '';

			foreach ( $conditions as $condition ) {
				if ( empty( $meta_box[ $condition ] ) ) {
					continue;
				}

				// Remove empty rules.
				$rules = array_filter( $meta_box[ $condition ] );
				$this->normalize_template_rule( $rules );

				if ( 1 === count( $rules ) && isset( $rules['relation'] ) ) {
					continue;
				}

				$data .= ' data-' . $condition . '="' . esc_attr( wp_json_encode( $rules ) ) . '"';
			}

			if ( $data ) {
				// Use <script> tag to prevent browser render, thus improves performance.
				echo '<script type="text/html" class="mb-show-hide"' . $data . '></script>';
			}
		}

		/**
		 * Enqueue plugin scripts
		 */
		public function enqueue() {
			list( , $url ) = RWMB_Loader::get_path( dirname( __FILE__ ) );
			wp_enqueue_script( 'mb-show-hide', $url . 'show-hide.js', array( 'jquery', 'underscore' ), '1.0.2', true );
		}

		public function get_post_id() {
			$post_id = null;
			if ( isset( $_GET['post'] ) ) {
				$post_id = intval( $_GET['post'] );
			} elseif ( isset( $_POST['post_ID'] ) ) {
				$post_id = intval( $_POST['post_ID'] );
			}
			return $post_id;
		}

		public function get_template( $post_id ) {
			return get_post_meta( $post_id, '_wp_page_template', true );
		}

		public function normalize_template_rule( &$rules ) {
			if ( ! isset( $rules['template'] ) ) {
				return;
			}
			$post_type = get_post_type( get_the_ID() );
			$tem = array();
			foreach ( $rules['template'] as $template ) {
				$tem[] = str_replace( $post_type . ':', '', $template );
			}
			$rules['template'] = $tem;
		}
	}

	new MB_Show_Hide();
}
