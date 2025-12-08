<?php
namespace MBTemplate;

use MetaBox\Support\Arr;
use Spyc;

class Parser {
	private $valid = true;

	public function parse() : array {
		return array_merge( $this->parse_user_input(), $this->parse_files() );
	}

	public function valid() : bool {
		return $this->valid;
	}

	private function parse_user_input() : array {
		$source = $this->get_option( 'source' );
		// Normalize tabs to 2 spaces.
		$source = str_replace( "\t", '  ', $source );

		return $this->parse_yaml( $source );
	}

	private function parse_files() : array {
		// Convert list of files (separated by commas) to array.
		$files = Arr::from_csv( $this->get_option( 'file' ) );

		// Allow developers to add/remove more files.
		$files = apply_filters( 'meta_box_template_files', $files );

		// Get full path of files.
		$files = array_map( [ $this, 'get_file_path' ], $files );

		$files   = array_filter( $files, 'file_exists' );
		$folders = array_filter( $files, 'is_dir' );
		$files   = array_diff( $files, $folders );

		// Get all files in all folders.
		$folder_files = array_map( [ $this, 'get_dir_files' ], $folders );

		$folder_files = $folder_files ? call_user_func_array( 'array_merge', $folder_files ) : [];
		$files        = array_merge( $files, $folder_files );

		// Parse files to get meta boxes.
		$meta_boxes = array_map( [ $this, 'parse_yaml' ], $files );
		$meta_boxes = $meta_boxes ? call_user_func_array( 'array_merge', $meta_boxes ) : [];

		return $meta_boxes;
	}

	private function get_option( string $name ) : string {
		$option = get_option( 'meta_box_template', [] ) ?: [];
		$value  = $option[ $name ] ?? '';
		return (string) $value;
	}

	private function parse_yaml( string $input ) : array {
		if ( empty( $input ) ) {
			return [];
		}

		$meta_boxes = Spyc::YAMLLoad( $input );
		if ( ! is_array( $meta_boxes ) ) {
			$this->valid = false;
			return [];
		}

		// Single meta box.
		if ( isset( $meta_boxes['title'] ) ) {
			$meta_boxes = [ $meta_boxes ];
		}

		// Make sure all meta boxes are arrays.
		$meta_boxes = array_filter( $meta_boxes, function( $meta_box ) {
			$is_array = is_array( $meta_box );
			if ( ! $is_array ) {
				$this->valid = false;
			}
			return $is_array;
		} );

		return $meta_boxes;
	}

	private function get_file_path( string $file ) : string {
		return strtr( $file, [
			'%wp-content%' => WP_CONTENT_DIR,
			'%plugins%'    => WP_PLUGIN_DIR,
			'%themes%'     => get_theme_root(),
			'%template%'   => get_template_directory(),
			'%stylesheet%' => get_stylesheet_directory(),
		] );
	}

	private function get_dir_files( string $dir ) : array {
		return array_merge( glob( "$dir/*.yaml" ), glob( "$dir/*.yml" ) );
	}
}
