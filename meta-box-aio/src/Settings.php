<?php
namespace MBAIO;

use RWMB_Switch_Field;
use MetaBox\Updater\Option;

class Settings {
	private $option_name = 'meta_box_aio';

	public function __construct() {
		$this->migrate_settings();
		$this->auto_activate_extensions();

		add_action( 'init', [ $this, 'init' ], 0 );
		add_action( 'wp_ajax_mbaio_toggle_extension', [ $this, 'ajax_toggle_extension' ] );
	}

	/**
	 * Migrate the settings from the previous options to the new one.
	 * Do not save settings in the form of 'extension' => true.
	 * Instead save an array of active extensions.
	 */
	private function migrate_settings(): void {
		$option = get_option( $this->option_name );
		if ( empty( $option ) || isset( $option['extensions'] ) ) {
			return;
		}

		$dir        = dirname( __DIR__ ) . '/vendor/meta-box';
		$extensions = glob( "$dir/*", GLOB_ONLYDIR );
		$extensions = array_map( 'basename', $extensions );

		$option     = array_filter( $option );
		$extensions = array_intersect( $extensions, array_keys( $option ) );
		$option     = [
			'extensions' => $extensions,
		];
		update_option( $this->option_name, $option );
	}

	private function auto_activate_extensions(): void {
		$option = get_option( $this->option_name );
		if ( isset( $option['extensions'] ) ) {
			return;
		}
		$option['extensions'] = wp_list_pluck( $this->get_extensions(), 'slug' );
		update_option( $this->option_name, $option );
	}

	public function init(): void {
		// Allows developers to bypass the settings page by filter.
		if ( false === apply_filters( 'mb_aio_show_settings', true ) ) {
			return;
		}

		// Show Meta Box admin menu.
		add_filter( 'rwmb_admin_menu', '__return_true' );
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
	}

	public function add_settings_page() {
		$page_hook = add_submenu_page(
			'meta-box',
			esc_html__( 'Extensions', 'meta-box-aio' ),
			esc_html__( 'Extensions', 'meta-box-aio' ),
			'manage_options',
			'meta-box-aio',
			[ $this, 'render' ]
		);
		add_action( "admin_print_styles-{$page_hook}", [ $this, 'enqueue' ] );
	}

	public function enqueue(): void {
		$builder_dir = META_BOX_AIO_DIR . '/vendor/meta-box/meta-box-builder';
		$builder_url = META_BOX_AIO_URL . 'vendor/meta-box/meta-box-builder';

		wp_enqueue_style(
			'mbb-app',
			$builder_url . '/assets/css/style.css',
			[],
			filemtime( $builder_dir . '/assets/css/style.css' )
		);
		wp_enqueue_style( 'rwmb-switch', RWMB_CSS_URL . 'switch.css', [], RWMB_VER );
		wp_enqueue_style( 'meta-box-dashboard', RWMB_URL . 'src/Dashboard/assets/css/dashboard.css', [], filemtime( RWMB_DIR . 'src/Dashboard/assets/css/dashboard.css' ) );
		wp_enqueue_style( 'meta-box-aio', plugin_dir_url( __DIR__ ) . 'assets/aio.css', [], '1.23.0' );

		wp_register_script( 'tippy', 'https://cdn.jsdelivr.net/combine/npm/@popperjs/core@2.11.2/dist/umd/popper.min.js,npm/tippy.js@6.3.7/dist/tippy-bundle.umd.min.js', [], '6.3.7', true );
		wp_enqueue_script( 'meta-box-aio', plugin_dir_url( __DIR__ ) . 'assets/aio.js', [ 'tippy' ], '1.23.0', true );

		wp_localize_script( 'meta-box-aio', 'mbAioEtxs', [
			'nonce' => wp_create_nonce( 'mbaio_nonce' ),
		] );
	}


	public function render(): void {
		if ( ! $this->is_license_active() ) {
			$this->show_license_warning();
			return;
		}

		$extensions        = $this->get_extensions();
		$option            = get_option( $this->option_name );
		$active_extensions = isset( $option['extensions'] ) ? $option['extensions'] : [];

		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		?>
		<div class="wrap mbaio-extensions">
			<?php $this->get_header(); ?>
			<div class="mb-body">
				<div class="mbaio-ajax" style="visibility: hidden;">
					<!-- For displaying ajax message -->
					<div class="message"></div>
				</div>
				<div class="mb-body__inner">
					<div class="mb-main">
						<div class="mb-box">
							<div class="mb-box__header mb-flex">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 4 4 19h16L12 4zm0 3.2 5.5 10.3H12V7.2z"></path></svg>
								<span class="mb-box__title"><?php esc_html_e( 'Filter:', 'meta-box-aio' ); ?></span>
								<ul class="mbaio-filter">
									<li><a href="#" data-filter=""><?php esc_html_e( 'All', 'meta-box-aio' ); ?></a></li>
									<li><a href="#" data-filter="premium"><?php esc_html_e( 'Premium', 'meta-box-aio' ); ?></a></li>
									<li><a href="#" data-filter="free"><?php esc_html_e( 'Free', 'meta-box-aio' ); ?></a></li>
									<li><a href="#" data-filter="popular"><?php esc_html_e( 'Popular', 'meta-box-aio' ); ?></a></li>
									<li><a href="#" data-filter="data"><?php esc_html_e( 'Data', 'meta-box-aio' ); ?></a></li>
									<li><a href="#" data-filter="ui"><?php esc_html_e( 'UI', 'meta-box-aio' ); ?></a></li>
									<li><a href="#" data-filter="integration"><?php esc_html_e( 'Integration', 'meta-box-aio' ); ?></a></li>
									<li><a href="#" data-filter="admin"><?php esc_html_e( 'Admin', 'meta-box-aio' ); ?></a></li>
									<li><a href="#" data-filter="frontend"><?php esc_html_e( 'Frontend', 'meta-box-aio' ); ?></a></li>
								</ul>
							</div>
							<div class="mb-box__body">
								<table class="widefat mbaio-list">
									<?php foreach ( $extensions as $extension ) : ?>
										<?php
										$info  = "https://metabox.io/plugins/{$extension['slug']}/?utm_source=settings_page&utm_medium=link&utm_campaign=aio";
										$docs  = "https://docs.metabox.io/extensions/{$extension['slug']}/?utm_source=settings_page&utm_medium=link&utm_campaign=aio";
										$forum = "https://metabox.io/support/forum/{$extension['slug']}/?utm_source=settings_page&utm_medium=link&utm_campaign=aio";

										if ( isset( $extension['info'] ) ) {
											$info = $extension['info'];
										}
										if ( isset( $extension['docs'] ) ) {
											$docs = $extension['docs'];
										}
										if ( isset( $extension['forum'] ) ) {
											$forum = $extension['forum'];
										}
										$is_active = in_array( $extension['slug'], $active_extensions, true );

										// Use the switch markup and styles from Meta Box.
										$field  = RWMB_Switch_Field::normalize( [
											'type'       => 'switch',
											'attributes' => [
												'value' => esc_attr( $extension['slug'] ),
											],
										] );
										$switch = RWMB_Switch_Field::html( $is_active, $field );
										?>
										<tr>
											<th>
												<?php echo $switch; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											</th>
											<td>
												<a target="_blank" class="mbaio-tooltip" data-tippy-content="<?php echo esc_attr( $extension['desc'] ) ?>" href="<?php echo esc_url( $info ) ?>"><?php echo esc_html( $extension['title'] ) ?></a>
												<?php if ( isset( $extension['plugin'] ) ) : ?>
													<?php // Translators: %s - Plugin name ?>
													<?php $this->tooltip( sprintf( __( 'This extension requires plugin %s to be installed and activated.', 'meta-box-aio' ), $extension['plugin'] ), 'warning' ) ?>
												<?php endif; ?>
											</td>
											<td class="mbaio-link">
												<?php if ( $docs ) : ?>
													<a target="_blank" href="<?php echo esc_url( $docs ) ?>"><?php esc_html_e( 'Docs', 'meta-box-aio' ) ?></a>
												<?php endif; ?>
											</td>
											<td class="mbaio-link">
												<?php if ( $forum ) : ?>
													<a target="_blank" href="<?php echo esc_url( $forum ) ?>"><?php esc_html_e( 'Forum', 'meta-box-aio' ) ?></a>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</table>
							</div>
						</div>
					</div>
				</div><!-- .mb-body__inner -->
			</div><!-- .mb-body -->
		</div>
		<?php
	}

	public function show_license_warning(): void {
		$settings_page = $this->get_updater()->is_network_activated() ? network_admin_url( 'settings.php?page=meta-box-updater' ) : admin_url( 'admin.php?page=meta-box-updater' );

		$status   = $this->get_updater()->get_license_status();
		$messages = [
			// Translators: %1$s - URL to the settings page.
			'no_key'  => __( 'You have not set your Meta Box license key yet. Please <a href="%1$s">enter your license key</a> to continue.', 'meta-box-aio' ),
			// Translators: %1$s - URL to the settings page.
			'invalid' => __( 'Your license key for Meta Box is <b>invalid</b>. Please <a href="%1$s">update your license key</a> to continue.', 'meta-box-aio' ),
			// Translators: %1$s - URL to the settings page.
			'error'   => __( 'Your license key for Meta Box is <b>invalid</b>. Please <a href="%1$s">update your license key</a> to continue.', 'meta-box-aio' ),
			// Translators: %2$s - URL to the My Account page.
			'expired' => __( 'Your license key for Meta Box is <b>expired</b>. Please <a href="%2$s" target="_blank">renew your license</a> to continue.', 'meta-box-aio' ),
		];

		?>
		<div class="wrap mbaio-extensions">
			<?php $this->get_header(); ?>
			<div class="mb-body">
				<div class="mb-body__inner">
					<div class="mb-main">
						<div class="mb-box">
							<div class="mbaio-license-warning">
								<h2>
									<span class="dashicons dashicons-warning"></span>
									<?php esc_html_e( 'License Warning', 'meta-box-aio' ) ?>
								</h2>
								<?php echo wp_kses_post( sprintf( $messages[ $status ], $settings_page, 'https://elu.to/aiosa' ) ); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	private function get_header(): void {
		?>
		<header class="mb-header mb-flex mb-dashboard">
			<div class="mb-header__left">
				<?php include META_BOX_AIO_DIR . '/assets/logo.svg'; ?>
				<h1><?php esc_html_e( 'Extensions', 'meta-box-aio' ); ?></h1>
			</div>

			<div class="mb-dashboard__header__icons">
				<div class="mb-dashboard__header__social">
					<a href="https://www.facebook.com/groups/metaboxusers" target="_blank">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-label="Facebook">
							<path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z"></path>
						</svg>
					</a>
					<a href="https://www.youtube.com/c/MetaBoxWP" target="_blank">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-label="Youtube">
							<path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"></path>
						</svg>
					</a>
					<a href="https://x.com/wpmetabox" target="_blank">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-label="X">
							<path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"></path>
						</svg>
					</a>
					<a href="https://www.linkedin.com/company/meta-box/" target="_blank">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-label="LinkedIn">
							<circle cx="4.983" cy="5.009" r="2.188"></circle>
							<path d="M9.237 8.855v12.139h3.769v-6.003c0-1.584.298-3.118 2.262-3.118 1.937 0 1.961 1.811 1.961 3.218v5.904H21v-6.657c0-3.27-.704-5.783-4.526-5.783-1.835 0-3.065 1.007-3.568 1.96h-.051v-1.66H9.237zm-6.142 0H6.87v12.139H3.095z"></path>
						</svg>
					</a>
				</div>
				<div class="mb-dashboard__header__links">
					<a href="https://docs.metabox.io" target="_blank" class="mb-dashboard__tooltip" data-tooltip="<?php esc_attr_e( 'Documentation', 'meta-box-aio' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
						</svg>
					</a>
					<a href="#" class="mb-dashboard__tooltip" data-tooltip="<?php esc_attr_e( 'My Account', 'meta-box-aio' ); ?>" data-position="bottom-right">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<circle class="cls-1" cx="12" cy="7.25" r="5.73"/><path class="cls-1" d="M1.5,23.48l.37-2.05A10.3,10.3,0,0,1,12,13h0a10.3,10.3,0,0,1,10.13,8.45l.37,2.05"/>
						</svg>
					</a>
				</div>
			</div>
		</header>
		<?php
	}

	public function ajax_toggle_extension(): void {
		check_ajax_referer( 'mbaio_nonce' );

		if ( empty( $_POST['slug'] ) ) {
			wp_send_json_error( __( 'An error has occurred.', 'meta-box-aio' ) );
		}

		$slug   = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
		$active = intval( $_POST['active'] ?? 0 );

		$option = get_option( $this->option_name, [] );
		$exts   = $option['extensions'] ?? [];

		if ( ! $active ) {
			$exts = array_diff( $exts, [ $slug ] );
		} elseif ( ! in_array( $slug, $exts, true ) ) {
			$exts[] = $slug;
		}

		$option['extensions'] = array_values( $exts );
		update_option( $this->option_name, $option );

		wp_send_json_success( [ 'message' => __( 'Extension updated', 'meta-box-aio' ) ] );
	}

	private function get_extensions(): array {
		$extensions = [
			[
				'slug'   => 'mb-acf-migration',
				'title'  => 'MB ACF Migration',
				'desc'   => __( 'Migrate field groups and custom fields from Advanced Custom Fields to Meta Box', 'meta-box-aio' ),
				'plugin' => 'Advanced Custom Fields',
			],
			[
				'slug'  => 'mb-admin-columns',
				'title' => 'MB Admin Columns',
				'desc'  => __( 'Display custom fields in table columns in admin screens for All Posts (types).', 'meta-box-aio' ),
			],
			[
				'slug'  => 'mb-blocks',
				'title' => 'MB Blocks',
				'desc'  => __( 'Creating custom Gutenberg blocks with PHP. No React, Webpack or Babel. Beautiful syntax, powerful features.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'mb-comment-meta',
				'title' => 'MB Comment Meta',
				'docs'  => false,
				'desc'  => __( 'Add custom fields to comments in WordPress. Support all field types and options.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'mb-custom-post-type',
				'title' => 'MB Custom Post Type',
				'info'  => 'https://metabox.io/plugins/custom-post-type/',
				'desc'  => __( 'Create and manage custom post types easily in WordPress with an easy-to-use interface.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'mb-custom-table',
				'title' => 'MB Custom Table',
				'desc'  => __( 'Save custom fields data to custom table instead of the default meta tables. Reduce database size and increase performance.', 'meta-box-aio' ),
			],
			[
				'slug'   => 'mb-divi-integrator',
				'title'  => 'MB Divi Integrator',
				'docs'   => false,
				'desc'   => __( 'Connect and display custom fields created by the Meta Box plugin in the Divi.', 'meta-box-aio' ),
				'plugin' => 'Divi',
			],
			[
				'slug'  => 'mb-frontend-submission',
				'title' => 'MB Frontend Submission',
				'desc'  => __( 'Create editorial forms so users can submit blog posts on the front end.', 'meta-box-aio' ),
			],
			[
				'slug'   => 'mb-rank-math',
				'title'  => 'MB Rank Math',
				'desc'   => __( 'Add content of custom fields to Rank Math Content Analysis to have better/correct SEO score.', 'meta-box-aio' ),
				'plugin' => 'Rank Math',
			],
			[
				'slug'  => 'mb-relationships',
				'title' => 'MB Relationships',
				'desc'  => __( 'A lightweight WordPress plugin for creating many-to-many relationships between posts, terms and users.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'mb-rest-api',
				'title' => 'MB REST API',
				'desc'  => __( 'Pull all meta value from posts, terms into the WP REST API responses.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'mb-revision',
				'title' => 'MB Revision',
				'desc'  => __( 'Track changes of custom fields with WordPress revision. Save, compare, restore the changes easily.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'mb-settings-page',
				'title' => 'MB Settings Page',
				'desc'  => __( 'Create impressive and robust custom settings pages in a few clicks.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'mb-term-meta',
				'title' => 'MB Term Meta',
				'desc'  => __( 'Easily add custom fields to categories, tags or any custom taxonomy.', 'meta-box-aio' ),
			],
			[
				'slug'   => 'mb-toolset-migration',
				'title'  => 'MB Toolset Migration',
				'desc'   => __( 'Migrate post types, field groups, custom fields and relationships from Toolset to Meta Box', 'meta-box-aio' ),
				'plugin' => 'Toolset',
			],
			[
				'slug'  => 'mb-user-meta',
				'title' => 'MB User Meta',
				'desc'  => __( 'Add custom fields to user profile (user meta) quickly with simple syntax.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'mb-user-profile',
				'title' => 'MB User Profile',
				'desc'  => __( 'Create register, login and edit user profile forms in the frontend. Embed everywhere with shortcodes.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'mb-views',
				'title' => 'MB Views',
				'desc'  => __( 'Build front-end templates for WordPress without touching theme files. Support Twig and all field types.', 'meta-box-aio' ),
			],
			[
				'slug'   => 'meta-box-beaver-themer-integrator',
				'title'  => 'MB Beaver Builder Integration',
				'docs'   => false,
				'desc'   => __( 'Select and show custom fields created by the Meta Box plugin in the Beaver Themer field connection.', 'meta-box-aio' ),
				'plugin' => 'Beaver Themer',
			],
			[
				'slug'  => 'meta-box-builder',
				'title' => 'MB Builder',
				'desc'  => __( 'Drag and drop your custom fields into place without a single line of code.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'meta-box-columns',
				'title' => 'MB Columns',
				'desc'  => __( 'Display fields more beautiful by putting them into 12-columns grid.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'meta-box-conditional-logic',
				'title' => 'MB Conditional Logic',
				'desc'  => __( 'Control when and where meta boxes, fields and HTML elements appear.', 'meta-box-aio' ),
			],
			[
				'slug'   => 'mb-elementor-integrator',
				'title'  => 'MB Elementor Integration',
				'docs'   => false,
				'desc'   => __( 'Connect and display custom fields created by the Meta Box plugin in the Elementor\'s dynamic tags.', 'meta-box-aio' ),
				'plugin' => 'Elementor Pro',
			],
			[
				'slug'   => 'meta-box-facetwp-integrator',
				'title'  => 'MB FacetWP Integration',
				'docs'   => false,
				'desc'   => __( 'Integrates Meta Box and FacetWP, makes custom fields searchable and filterable in the frontend.', 'meta-box-aio' ),
				'plugin' => 'FacetWP',
			],
			[
				'slug'  => 'meta-box-geolocation',
				'title' => 'MB Geolocation',
				'desc'  => __( 'Automatically and instantly populate location data with the power of Google Maps Geolocation API.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'meta-box-group',
				'title' => 'MB Group',
				'desc'  => __( 'Organize custom fields into robust and intensely user-friendly groups.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'meta-box-include-exclude',
				'title' => 'MB Include Exclude',
				'desc'  => __( 'Show or hide meta boxes whenever and for whomever you choose.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'meta-box-show-hide',
				'title' => 'MB Show Hide',
				'desc'  => __( 'Toggle meta boxes by page template, post format or taxonomy using JS.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'meta-box-tabs',
				'title' => 'MB Tabs',
				'desc'  => __( 'Add as many custom fields as you want and organize them into tabs.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'meta-box-template',
				'title' => 'MB Template',
				'desc'  => __( 'Define custom meta boxes and custom fields easier with templates.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'meta-box-text-limiter',
				'title' => 'MB Text Limiter',
				'docs'  => false,
				'desc'  => __( 'Limit the number of characters or words entered for text and textarea fields.', 'meta-box-aio' ),
			],
			[
				'slug'  => 'meta-box-tooltip',
				'title' => 'MB Tooltip',
				'desc'  => __( 'Display help information for fields using beautiful tooltips.', 'meta-box-aio' ),
			],
			[
				'slug'   => 'meta-box-yoast-seo',
				'title'  => 'MB Yoast SEO Integration',
				'docs'   => false,
				'forum'  => 'https://metabox.io/support/forum/meta-box-for-yoast-seo/',
				'desc'   => __( 'Add content of custom fields to Yoast SEO Content Analysis to have better/correct SEO score.', 'meta-box-aio' ),
				'plugin' => 'Yoast SEO',
			],
		];

		$slugs = wp_list_pluck( $extensions, 'slug' );
		$slugs = apply_filters( 'mb_aio_extensions', $slugs );
		$slugs = array_unique( $slugs );

		$extensions = array_filter(
			$extensions, function ( $extension ) use ( $slugs ) {
				return in_array( $extension['slug'], $slugs, true );
			}
		);

		return $extensions;
	}

	private function tooltip( $content, $icon = 'info' ): void {
		if ( 'info' === $icon ) {
			echo '<button type="button" class="mbaio-tooltip" data-tippy-content="' . esc_attr( $content ) . '"><span class="dashicons dashicons-editor-help"></span></button>';
			return;
		}
		echo '<button type="button" class="mbaio-tooltip" data-tippy-content="' . esc_attr( $content ) . '">';
		include dirname( __DIR__ ) . '/assets/warning.svg';
		echo '</button>';
	}

	private function is_license_active(): bool {
		return $this->get_updater()->get_license_status() === 'active';
	}

	private function get_updater(): Option {
		static $updater;

		if ( ! $updater ) {
			$updater = new Option();
		}

		return $updater;
	}
}
