<?php
namespace MBTemplate;

class Settings {
	private $parser;

	public function __construct( Parser $parser ) {
		$this->parser = $parser;

		add_action( 'admin_init', [ $this, 'register_setting' ] );
		add_action( 'admin_menu', [ $this, 'add_plugin_menu' ] );
	}

	public function register_setting() {
		register_setting( 'meta_box_template', 'meta_box_template' );

		add_settings_section( 'general', '', '__return_null', 'meta-box-template' );
		add_settings_field( 'template', __( 'Enter template:', 'meta-box-template' ), [ $this, 'render_template_field' ], 'meta-box-template', 'general' );
		add_settings_field( 'file', __( 'Or specify path to config file(s):', 'meta-box-template' ), [ $this, 'render_file_field' ], 'meta-box-template', 'general' );
	}

	public function add_plugin_menu() {
		$page = add_submenu_page(
			'meta-box',
			__( 'Template', 'meta-box-template' ),
			__( 'Template', 'meta-box-template' ),
			'manage_options',
			'meta-box-template',
			[ $this, 'render_page' ]
		);
		add_action( "admin_print_styles-$page", [ $this, 'enqueue' ] );
		add_action( "load-$page", [ $this, 'notify' ] );
	}

	public function enqueue() {
		wp_enqueue_code_editor( [
			'type' => 'application/x-httpd-php',
		] );

		wp_enqueue_style( 'meta-box-template', MB_TEMPLATE_URL . 'assets/template.css', [ 'code-editor' ], filemtime( MB_TEMPLATE_DIR . '/assets/template.css' ) );
		wp_enqueue_script( 'meta-box-template', MB_TEMPLATE_URL . 'assets/template.js', [ 'jquery', 'code-editor' ], filemtime( MB_TEMPLATE_DIR . '/assets/template.js' ), true );
	}

	public function notify() {
		$this->validate();
		add_action( 'admin_notices', 'settings_errors' );
	}

	private function validate() {
		$this->parser->parse();
		if ( $this->parser->valid() ) {
			return;
		}
		add_settings_error(
			'',
			'saved',
			sprintf(
				// Translators: %s - URL to the documentation.
				__( 'SYNTAX ERROR: Your input or files contain invalid YAML for Meta Box. Please <a href="%s" target="_blank">follow the documentation</a> and try again.', 'meta-box-template' ),
				'https://docs.metabox.io/extensions/meta-box-template/#yaml-syntax'
			),
			'error'
		);
	}

	public function render_page() {
		?>
		<div class="wrap">
			<h1><?= esc_html( get_admin_page_title() ) ?></h1>

			<form action="options.php" method="post">

				<?php settings_fields( 'meta_box_template' ); ?>

				<?php do_settings_sections( 'meta-box-template' ); ?>

				<?php submit_button( __( 'Save Changes', 'meta-box-template' ) ); ?>

			</form>
		</div>
		<?php
	}

	public function render_template_field() {
		$option = get_option( 'meta_box_template' );
		$source = $option['source'] ?? '';
		?>
		<textarea class="code large-text" rows="20" name="meta_box_template[source]" id="meta-box-template"><?= esc_textarea( $source ); ?></textarea>
		<p class="description">
			<?=
			wp_kses_post( sprintf(
				// Translators: %s - docs link.
				__( 'Supports YAML format. See <a href="%s" target="_blank">documentation</a>.', 'meta-box-template' ),
				'https://docs.metabox.io/extensions/meta-box-template/'
			) );
			?>
		</p>
		<?php
	}

	public function render_file_field() {
		$option = get_option( 'meta_box_template' );
		$file   = $option['file'] ?? '';
		?>
		<input type="text" class="large-text" name="meta_box_template[file]" value="<?= esc_attr( $file ); ?>">
		<p class="description">
			<?= wp_kses_post( __( 'Enter absolute path to <code>.yml</code> files or folders containing <code>.yml</code> files. Separate multiple items with commas. Supports following variables (no trailing slash):', 'meta-box-template' ) ); ?>
		</p>
		<table class="mbt-table">
			<tr>
				<td><code>%wp-content%</code></td>
				<td><?= wp_kses_post( _e( 'Path to <code>wp-content</code> directory', 'meta-box-template' ) ); ?></td>
			</tr>
			<tr>
				<td><code>%plugins%</code></td>
				<td><?= wp_kses_post( _e( 'Path to <code>wp-content/plugins</code> directory', 'meta-box-template' ) ); ?></td>
			</tr>
			<tr>
				<td><code>%themes%</code></td>
				<td>
					<?=
					wp_kses_post( sprintf(
						// Translators: %s - function URL.
						__( 'Path to <code>wp-content/themes</code> directory. Same as the <a href="%s" target="_blank">get_theme_root()</a> function.', 'meta-box-template' ),
						'https://developer.wordpress.org/reference/functions/get_theme_root/'
					) );
					?>
				</td>
			</tr>
			<tr>
				<td><code>%template%</code></td>
				<td>
					<?=
					wp_kses_post( sprintf(
						// Translators: %s - function URL.
						__( 'Path to current theme directory. Same as the <a href="%s" target="_blank">get_template_directory()</a> function.', 'meta-box-template' ),
						'https://developer.wordpress.org/reference/functions/get_template_directory/'
					) );
					?>
				</td>
			</tr>
			<tr>
				<td><code>%stylesheet%</code></td>
				<td>
					<?=
					wp_kses_post( sprintf(
						// Translators: %s - function URL.
						__( 'Path to current child theme directory. Same as the <a href="%s" target="_blank">get_stylesheet_directory()</a> function.', 'meta-box-template' ),
						'https://developer.wordpress.org/reference/functions/get_stylesheet_directory/'
					) );
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}
