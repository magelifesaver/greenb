<?php
namespace MBViews\Renderer;

class Base {
	protected $meta_box_renderer;
	protected $data;

	public function __construct( $meta_box_renderer ) {
		$this->meta_box_renderer = $meta_box_renderer;
	}

	/**
	 * Must return true to make __get works.
	 */
	public function __isset( $name ) {
		return true;
	}

	public function __get( $name ) {
		if ( empty( $this->data ) ) {
			$this->get_data();
		}

		return isset( $this->data[ $name ] ) ? $this->data[ $name ] : null;
	}
}
