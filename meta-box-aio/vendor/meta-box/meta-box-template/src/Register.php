<?php
namespace MBTemplate;

class Register {
	private $parser;
	private $meta_boxes = [];

	public function __construct( Parser $parser ) {
		$this->parser = $parser;

		add_filter( 'rwmb_meta_boxes', [ $this, 'register_meta_boxes' ] );
		add_action( 'admin_notices', [ $this, 'notify' ] );
	}

	public function register_meta_boxes( $meta_boxes ) : array {
		$meta_boxes = (array) $meta_boxes;
		$registered = $this->parse();

		return empty( $registered ) ? $meta_boxes : array_merge( $meta_boxes, $registered );
	}

	public function notify() {
		// Don't show notification twice on the settings page.
		if ( get_current_screen()->id === 'meta-box_page_meta-box-template' ) {
			return;
		}

		$this->parse();
		if ( $this->parser->valid() ) {
			return;
		}

		printf(
			// Translators: %s - error message.
			'<div id="meta-box-template" class="notice notice-error is-dismissible"><p><span class="dashicons dashicons-warning" style="color: #d63638"></span> %s</p></div>',
			wp_kses_post( sprintf(
				// Translators: %s - URL to the plugin settings page.
				__( 'Your input or files contain invalid YAML for Meta Box. Please go to the <a href="%s">plugin settings page</a> and fix it.', 'meta-box-template' ),
				admin_url( 'admin.php?page=meta-box-template' )
			) )
		);
	}

	private function parse() : array {
		return $this->meta_boxes ?: array_filter( $this->parser->parse() );
	}
}
