<?php
namespace MBViews;

class AdminColumns {
	public function __construct() {
		add_filter( 'manage_mb-views_posts_columns', [ $this, 'add_columns' ] );
		add_action( 'manage_mb-views_posts_custom_column', [ $this, 'show_column' ] );
	}

	public function add_columns( $columns ) {
		$new_columns = array(
			'name'      => __( 'Name', 'mb-views' ),
			'type'      => __( 'Type', 'mb-views' ),
			'location'  => __( 'Location', 'mb-views' ),
			'shortcode' => __( 'Shortcode', 'mb-views' ),
		);
		$columns     = array_slice( $columns, 0, 2, true ) + $new_columns + array_slice( $columns, 2, null, true );
		return $columns;
	}

	public function show_column( $name ) {
		if ( ! in_array( $name, [ 'name', 'type', 'location', 'shortcode' ], true ) ) {
			return;
		}
		$this->{"show_$name"}();
	}

	private function show_name() {
		echo esc_html( get_post_field( 'post_name' ) );
	}

	private function show_type() {
		rwmb_the_value( 'type' );
	}

	private function show_location() {
		$type = rwmb_get_value( 'type' );
		if ( ! in_array( $type, [ 'archive', 'singular', 'action' ], true ) ) {
			esc_html_e( 'Custom', 'mb-views' );
			return;
		}
		if ( 'action' === $type ) {
			$priority = get_post_meta( get_the_ID(), 'action_priority', true );
			$priority = $priority ?: 10;
			echo '<code>', esc_html( get_post_meta( get_the_ID(), 'mbv_action', true ) ), ': ', esc_html( $priority ), '</code><br>';
			$type = get_post_meta( get_the_ID(), 'action_type', true );
		}
		$rules = get_post_meta( get_the_ID(), "{$type}_locations", true );
		if ( empty( $rules ) ) {
			return;
		}
		$locations = Location\Settings::{"get_{$type}_locations"}();
		$output    = [];
		foreach ( $rules as $group ) {
			$group_output = [];
			foreach ( $group as $rule ) {
				$group_output[] = $this->get_rule_name( $rule['name'], $locations ) . ": {$rule['label']}";
			}
			$group_output = implode( '<br>', $group_output );
			$output[]     = $group_output;
		}
		echo wp_kses_post( implode( '<hr>', $output ) );
	}

	private function get_rule_name( $rule, $locations ) {
		foreach ( $locations as $group ) {
			foreach ( $group['options'] as $option ) {
				if ( $option['value'] === $rule ) {
					return $option['label'];
				}
			}
		}
		return null;
	}

	private function show_shortcode() {
		$shortcode = '[mbv name="' . get_post()->post_name . '"]';
		echo '<input type="text" readonly value="' . esc_attr( $shortcode ) . '" onclick="this.select()">';
	}
}
