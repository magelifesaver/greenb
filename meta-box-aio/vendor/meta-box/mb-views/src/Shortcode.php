<?php
namespace MBViews;

class Shortcode {
	private $renderer;

	public function __construct( $renderer ) {
		$this->renderer = $renderer;

		add_shortcode( 'mbv', [ $this, 'render' ] );
	}

	public function render( $atts ) {
		$atts = array_merge( [
			'id'   => null,
			'name' => '',
		], $atts );

		$key = null;
		if ( ! empty( $atts['id'] ) ) {
			$key = $atts['id'];
			unset( $atts['id'] );
		} elseif ( ! empty( $atts['name'] ) ) {
			$key = $atts['name'];
			unset( $atts['name'] );
		}

		return $key ? $this->renderer->render( $key, $atts ) : '';
	}
}
