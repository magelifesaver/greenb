<?php
namespace MBViews;

class Import {
	public function __construct() {
		add_action( 'admin_footer-edit.php', [ $this, 'output_js_templates' ] );
		add_action( 'admin_print_styles-edit.php', [ $this, 'enqueue' ] );
		add_action( 'admin_init', [ $this, 'import' ] );
	}

	public function enqueue() {
		if ( 'edit-mb-views' !== get_current_screen()->id ) {
			return;
		}

		wp_enqueue_style( 'mbv-import', MBV_URL . 'assets/import.css', [], filemtime( MBV_DIR . '/assets/import.css' ) );
		wp_enqueue_script( 'mbv-import', MBV_URL . 'assets/import.js', [ 'jquery' ], filemtime( MBV_DIR . '/assets/import.js' ), true );

		wp_localize_script( 'mbv-import', 'MBV', [
			'export' => esc_html__( 'Export', 'mb-views' ),
			'import' => esc_html__( 'Import', 'mb-views' ),
		] );
	}

	public function output_js_templates() {
		if ( 'edit-mb-views' !== get_current_screen()->id ) {
			return;
		}

		?>
		<?php if ( isset( $_GET['imported'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Views have been imported successfully!', 'mb-views' ); ?></p></div>
		<?php endif; ?>

		<script type="text/template" id="mbv-import-form">
			<div class="mbv-import-form">
				<p><?php esc_html_e( 'Choose an exported ".json" file from your computer:', 'mb-views' ); ?></p>
				<form enctype="multipart/form-data" method="post" action="">
					<?php wp_nonce_field( 'import' ); ?>
					<input type="file" name="mbv_file">
					<?php submit_button( esc_attr__( 'Import', 'mb-views' ), 'secondary', 'submit', false, [ 'disabled' => true ] ); ?>
				</form>
			</div>
		</script>
		<?php
	}

	public function import() {
		if ( empty( $_FILES['mbv_file'] ) || empty( $_FILES['mbv_file']['tmp_name'] ) ) {
			return;
		}

		check_ajax_referer( 'import' );

		$url    = admin_url( 'edit.php?post_type=mb-views' );
		$data   = file_get_contents( sanitize_text_field( $_FILES['mbv_file']['tmp_name'] ) );
		$result = $this->import_json( $data );

		if ( ! $result ) {
			// Translators: %s - go back URL.
			wp_die( wp_kses_post( sprintf( __( 'Invalid file data. <a href="%s">Go back</a>.', 'mb-views' ), $url ) ) );
		}

		$url = add_query_arg( 'imported', 'true', $url );
		wp_safe_redirect( $url );
		die;
	}

	private function import_json( $data ) {
		$posts = json_decode( $data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}

		// If import only one post.
		if ( isset( $posts['post_title'] ) ) {
			$posts = [ $posts ];
		}

		foreach ( $posts as $post ) {
			$post_id = wp_insert_post( $post );
			if ( ! $post_id ) {
				wp_die( wp_kses_post( sprintf(
					// Translators: %s - go back URL.
					__( 'Cannot import the view <strong>%1$s</strong>. <a href="%2$s">Go back</a>.', 'mb-views' ),
					$post['title'],
					admin_url( 'edit.php?post_type=mb-views' )
				) ) );
			}
			if ( is_wp_error( $post_id ) ) {
				wp_die( wp_kses_post( implode( '<br>', $post_id->get_error_messages() ) ) );
			}

			foreach ( $post['settings'] as $meta => $value ) {
				$this->update_postmeta( $post_id, $meta, $value );
			}
		}

		return true;
	}
	private function update_postmeta( $post_id, $meta, $value ) {
		update_post_meta( $post_id, $meta, $value );
	}
}
