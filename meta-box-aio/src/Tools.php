<?php
namespace MBAIO;

use MetaBox\Support\Data;

class Tools {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_tools_submenu' ] );
		add_action( 'wp_ajax_mb_aio_remove_orphans', [ $this, 'remove_orphans' ] );
		add_action( 'wp_ajax_mb_aio_remove_fields', [ $this, 'remove_fields' ] );
		add_action( 'wp_ajax_mb_aio_change_fields', [ $this, 'change_fields' ] );
		add_action( 'wp_ajax_mb_aio_change_post_type', [ $this, 'change_post_type' ] );

		// Get options fields for autocomplete select2 filter.
		add_action( 'wp_ajax_mb_aio_type_filter', [ $this, 'ajax_get_meta_keys' ] );
	}

	public function add_tools_submenu(): void {
		$page = add_submenu_page(
			'meta-box',
			esc_html__( 'Tools', 'meta-box-aio' ),
			esc_html__( 'Tools', 'meta-box-aio' ),
			'manage_options',
			'meta-box-tools',
			[ $this, 'render' ]
		);
		add_action( "admin_print_styles-{$page}", [ $this, 'enqueue' ] );
	}

	public function enqueue(): void {
		wp_enqueue_style( 'wp-edit-post' );

		$builder_dir = META_BOX_AIO_DIR . '/vendor/meta-box/meta-box-builder';
		$builder_url = META_BOX_AIO_URL . 'vendor/meta-box/meta-box-builder';

		// Import Select2 plugin
		wp_enqueue_style( 'rwmb-select2', RWMB_CSS_URL . 'select2/select2.css', [], '4.0.10' );
		wp_enqueue_style( 'rwmb-select-advanced', RWMB_CSS_URL . 'select-advanced.css', [], RWMB_VER );
		wp_register_script( 'rwmb-select2', RWMB_JS_URL . 'select2/select2.min.js', [ 'jquery' ], '4.0.10', true );

		// Select2 localize
		$dependencies = [ 'rwmb-select2' ];
		$locale       = str_replace( '_', '-', get_user_locale() );
		$locale_short = substr( $locale, 0, 2 );
		$locale       = file_exists( RWMB_DIR . "js/select2/i18n/$locale.js" ) ? $locale : $locale_short;

		if ( file_exists( RWMB_DIR . "js/select2/i18n/$locale.js" ) ) {
			wp_register_script( 'rwmb-select2-i18n', RWMB_JS_URL . "select2/i18n/$locale.js", [ 'rwmb-select2' ], '4.0.10', true );
			$dependencies[] = 'rwmb-select2-i18n';
		}

		wp_enqueue_style( 'meta-box-dashboard', RWMB_URL . 'src/Dashboard/assets/css/dashboard.css', [], filemtime( RWMB_DIR . 'src/Dashboard/assets/css/dashboard.css' ) );
		wp_enqueue_style(
			'mbb-app',
			$builder_url . '/assets/css/style.css',
			[],
			filemtime( $builder_dir . '/assets/css/style.css' )
		);
		wp_enqueue_style(
			'meta-box-aio',
			META_BOX_AIO_URL . 'assets/aio.css',
			[],
			filemtime( META_BOX_AIO_DIR . '/assets/aio.css' )
		);

		wp_enqueue_script(
			'meta-box-aio-tools',
			META_BOX_AIO_URL . 'assets/tools.js',
			$dependencies,
			filemtime( META_BOX_AIO_DIR . '/assets/tools.js' ),
			true
		);

		wp_localize_script( 'meta-box-aio-tools', 'mbAioTools', [
			'nonce' => wp_create_nonce( 'mb_aio_tools_nonce' ),
			'texts' => [
				'confirm' => __( 'Are you sure you want to process? This action cannot be undone.', 'meta-box-aio' ),
			],
		] );
	}

	private function get_meta_keys( string $type = 'post' ): array {
		global $wpdb;

		switch ( $type ) {
			case 'term':
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->get_col( "SELECT DISTINCT meta_key FROM $wpdb->termmeta" );
				break;

			case 'user':
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->get_col( "SELECT DISTINCT meta_key FROM $wpdb->usermeta" );
				break;

			case 'all':
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$post_keys = $wpdb->get_col( "SELECT DISTINCT meta_key FROM $wpdb->postmeta" );
				$term_keys = $wpdb->get_col( "SELECT DISTINCT meta_key FROM $wpdb->termmeta" );
				$user_keys = $wpdb->get_col( "SELECT DISTINCT meta_key FROM $wpdb->usermeta" );
				// phpcs:enable
				$result = array_unique( array_merge( $post_keys, $term_keys, $user_keys ) );
				break;

			default:
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->get_col( "SELECT DISTINCT meta_key FROM $wpdb->postmeta" );
				break;
		}

		sort( $result );
		return $result;
	}

	public function ajax_get_meta_keys(): void {
		check_ajax_referer( 'mb_aio_tools_nonce' );

		if ( empty( $_POST['type'] ) ) {
			wp_send_json_error( __( 'Invalid type.', 'meta-box-aio' ) );
		}

		$type = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'post' ) );
		$keys = $this->get_meta_keys( $type );

		wp_send_json_success( $keys );
	}

	private function get_post_types(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$post_types = $wpdb->get_col( "SELECT DISTINCT post_type FROM $wpdb->posts" );
		$exclude    = Data::unsupported_post_types();
		$result     = array_diff( $post_types, $exclude );
		sort( $result );

		return $result;
	}

	public function render(): void {
		$meta_keys  = $this->get_meta_keys( 'post' );
		$post_types = $this->get_post_types();
		?>
		<div class="wrap mbaio-tools">
			<header class="mb-header mb-flex mb-dashboard">
				<div class="mb-header__left">
					<?php include META_BOX_AIO_DIR . '/assets/logo.svg'; ?>
					<h1><?php esc_html_e( 'Tools', 'meta-box-aio' ); ?></h1>
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

			<div class="mb-body">
				<div class="mb-body__inner">
					<div class="mb-main">
						<div class="mb-box">
							<div class="mb-box__header mb-flex">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
									<path d="M16.949 14.121 19.071 12a5.008 5.008 0 0 0 0-7.071 5.006 5.006 0 0 0-7.071 0l-.707.707 1.414 1.414.707-.707a3.007 3.007 0 0 1 4.243 0 3.005 3.005 0 0 1 0 4.243l-2.122 2.121a2.723 2.723 0 0 1-.844.57L13.414 12l1.414-1.414-.707-.707a4.965 4.965 0 0 0-3.535-1.465c-.235 0-.464.032-.691.066L3.707 2.293 2.293 3.707l18 18 1.414-1.414-5.536-5.536c.277-.184.538-.396.778-.636zm-6.363 3.536a3.007 3.007 0 0 1-4.243 0 3.005 3.005 0 0 1 0-4.243l1.476-1.475-1.414-1.414L4.929 12a5.008 5.008 0 0 0 0 7.071 4.983 4.983 0 0 0 3.535 1.462A4.982 4.982 0 0 0 12 19.071l.707-.707-1.414-1.414-.707.707z"></path>
								</svg>
								<span class="mb-box__title"><?php esc_html_e( 'Remove Orphan Fields', 'meta-box-aio' ); ?></span>
							</div>
							<div class="mb-box__body">
								<p><?php esc_html_e( 'This tool will remove all custom fields in the post, term and user meta tables that aren\'t linked to a valid post, term or user.', 'meta-box-aio' ); ?></p>
								<p><strong><?php esc_html_e( 'Please backup your database before doing this. This can\'t be reversed.', 'meta-box-aio' ); ?></strong></p>

								<form class="mb-form mb-flex">
									<input type="hidden" name="action" value="mb_aio_remove_orphans">
									<button type="submit" class="button button-primary" data-loading="<?php esc_attr_e( 'Removing...', 'meta-box-aio' ); ?>" data-text="<?php esc_attr_e( 'Remove', 'meta-box-aio' ); ?>">
										<?php esc_html_e( 'Remove', 'meta-box-aio' ); ?>
									</button>
								</form>

								<p style="display: none;"></p>
							</div>
						</div>

						<div class="mb-box">
							<div class="mb-box__header mb-flex">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
									<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
								</svg>
								<span class="mb-box__title"><?php esc_html_e( 'Remove Custom Fields', 'meta-box-aio' ); ?></span>
							</div>

							<div class="mb-box__body">
								<p><?php esc_html_e( 'This tool will remove all custom fields in the post, term and user meta tables that has a specified key.', 'meta-box-aio' ); ?></p>
								<p><strong><?php esc_html_e( 'Please backup your database before doing this. This can\'t be reversed.', 'meta-box-aio' ); ?></strong></p>

								<form class="mb-form mb-flex">
									<input type="hidden" name="action" value="mb_aio_remove_fields">

									<select name="key" class="mb-select2" data-placeholder="<?php esc_attr_e( 'Meta key', 'meta-box-aio' ); ?>" data-notfound="<?php esc_attr_e( 'No fields found', 'meta-box-aio' ); ?>" <?php disabled( empty( $meta_keys ) ) ?> >
										<?php if ( empty( $meta_keys ) ) : ?>
											<option selected disabled ><?php esc_attr_e( 'No fields found', 'meta-box-aio' ); ?></option>
										<?php else : ?>
											<option value=""></option>
											<?php foreach ( $meta_keys as $key ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $key ); ?></option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>

									<select name="type">
										<option value="post"><?php esc_html_e( 'Post', 'meta-box-aio' ); ?></option>
										<option value="term"><?php esc_html_e( 'Term', 'meta-box-aio' ); ?></option>
										<option value="user"><?php esc_html_e( 'User', 'meta-box-aio' ); ?></option>
										<option value="all"><?php esc_html_e( 'All', 'meta-box-aio' ); ?></option>
									</select>
									<button type="submit" class="button button-primary" data-loading="<?php esc_attr_e( 'Removing...', 'meta-box-aio' ); ?>" data-text="<?php esc_attr_e( 'Remove', 'meta-box-aio' ); ?>">
										<?php esc_html_e( 'Remove', 'meta-box-aio' ); ?>
									</button>
								</form>

								<p style="display: none;"></p>
							</div>
						</div>
						<div class="mb-box">
							<div class="mb-box__header mb-flex">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
									<path d="m19 7-3-3-8.5 8.5-1 4 4-1L19 7Zm-7 11.5H5V20h7v-1.5Z"></path>
								</svg>
								<span class="mb-box__title"><?php esc_html_e( 'Change Custom Field Key', 'meta-box-aio' ); ?></span>
							</div>

							<div class="mb-box__body">
								<p><?php esc_html_e( 'This tool will change the meta key of all custom fields in the post, term and user meta tables.', 'meta-box-aio' ); ?></p>
								<p><strong><?php esc_html_e( 'Please backup your database before doing this. This can\'t be reversed.', 'meta-box-aio' ); ?></strong></p>

								<form class="mb-form mb-flex">
									<input type="hidden" name="action" value="mb_aio_change_fields">

									<select name="original" class="mb-select2" data-placeholder="<?php esc_attr_e( 'Original key', 'meta-box-aio' ); ?>" data-notfound="<?php esc_attr_e( 'No fields found', 'meta-box-aio' ); ?>" <?php disabled( empty( $meta_keys ) ) ?> >
										<?php if ( empty( $meta_keys ) ) : ?>
											<option selected disabled ><?php esc_attr_e( 'No fields found', 'meta-box-aio' ); ?></option>
										<?php else : ?>
											<option value=""></option>
											<?php foreach ( $meta_keys as $key ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $key ); ?></option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>

									<input type="text" name="new" placeholder="<?php esc_attr_e( 'New key', 'meta-box-aio' ); ?>" required>
									<select name="type" data-placeholder="<?php esc_attr_e( 'Type', 'meta-box-aio' ); ?>">
										<option value="post"><?php esc_html_e( 'Post', 'meta-box-aio' ); ?></option>
										<option value="term"><?php esc_html_e( 'Term', 'meta-box-aio' ); ?></option>
										<option value="user"><?php esc_html_e( 'User', 'meta-box-aio' ); ?></option>
										<option value="all"><?php esc_html_e( 'All', 'meta-box-aio' ); ?></option>
									</select>
									<button type="submit" class="button button-primary" data-loading="<?php esc_attr_e( 'Changing...', 'meta-box-aio' ); ?>" data-text="<?php esc_attr_e( 'Change', 'meta-box-aio' ); ?>">
										<?php esc_html_e( 'Change', 'meta-box-aio' ); ?>
									</button>
								</form>

								<p style="display: none;"></p>
							</div>
						</div>
						<div class="mb-box">
							<div class="mb-box__header mb-flex">
								<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
									<path d="m6.249 11.065.44-.44h3.186l-1.5 1.5H7.31l-1.957 1.96A.792.792 0 0 1 4 13.524V5a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v1.5L12.5 8V5.5h-7v6.315l.749-.75ZM20 19.75H7v-1.5h13v1.5Zm0-12.653-8.967 9.064L8 17l.867-2.935L17.833 5 20 7.097Z"></path>
								</svg>
								<span class="mb-box__title"><?php esc_html_e( 'Change Post Type', 'meta-box-aio' ); ?></span>
							</div>

							<div class="mb-box__body">
								<p><?php esc_html_e( 'This tool will change the slug of a post type to another one.', 'meta-box-aio' ); ?></p>
								<p><strong><?php esc_html_e( 'Please backup your database before doing this. This can\'t be reversed.', 'meta-box-aio' ); ?></strong></p>

								<form class="mb-form mb-flex">
									<input type="hidden" name="action" value="mb_aio_change_post_type">
									<select name="original" data-placeholder="<?php esc_attr_e( 'Original post type', 'meta-box-aio' ); ?>">
										<?php foreach ( $post_types as $post_type ) : ?>
											<option value="<?php echo esc_attr( $post_type ); ?>"><?php echo esc_html( $post_type ); ?></option>
										<?php endforeach; ?>
									</select>
									<input type="text" name="new" placeholder="<?php esc_attr_e( 'New post type', 'meta-box-aio' ); ?>" required>
									<button type="submit" class="button button-primary" data-loading="<?php esc_attr_e( 'Changing...', 'meta-box-aio' ); ?>" data-text="<?php esc_attr_e( 'Change', 'meta-box-aio' ); ?>">
										<?php esc_html_e( 'Change', 'meta-box-aio' ); ?>
									</button>
								</form>

								<p style="display: none;"></p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
	}

	public function remove_orphans(): void {
		check_ajax_referer( 'mb_aio_tools_nonce' );

		global $wpdb;

		$post_meta_query = "
			DELETE pm FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.ID IS NULL AND pm.post_id IS NOT NULL
		";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$post_meta_deleted = $wpdb->query( $post_meta_query );

		$term_meta_query = "
			DELETE tm FROM {$wpdb->termmeta} tm
			LEFT JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
			WHERE t.term_id IS NULL AND tm.term_id IS NOT NULL
		";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$term_meta_deleted = $wpdb->query( $term_meta_query );

		$user_meta_query = "
			DELETE um FROM {$wpdb->usermeta} um
			LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID
			WHERE u.ID IS NULL AND um.user_id IS NOT NULL
		";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$user_meta_deleted = $wpdb->query( $user_meta_query );

		if ( 0 === $post_meta_deleted + $term_meta_deleted + $user_meta_deleted ) {
			wp_send_json_success( __( 'No orphan fields found.', 'meta-box-aio' ) );
		}

		wp_send_json_success(
			sprintf(
				// Translators: %1$d - post meta fields deleted, %2$d - term meta fields deleted, %3$d - user meta fields deleted
				__( 'Successfully removed orphaned custom fields: %1$d post meta, %2$d term meta, %3$d user meta.', 'meta-box-aio' ),
				$post_meta_deleted,
				$term_meta_deleted,
				$user_meta_deleted
			)
		);
	}

	public function remove_fields(): void {
		check_ajax_referer( 'mb_aio_tools_nonce' );

		global $wpdb;

		if ( empty( $_POST['key'] ) ) {
			wp_send_json_error( __( 'Please enter a custom field key.', 'meta-box-aio' ) );
		}
		$key  = sanitize_text_field( wp_unslash( $_POST['key'] ) );
		$type = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'post' ) );

		if ( ! in_array( $type, [ 'post', 'term', 'user', 'all' ], true ) ) {
			wp_send_json_error( __( 'Invalid field type.', 'meta-box-aio' ) );
		}

		$tables = $this->get_tables( $type );

		$deleted = [];
		foreach ( $tables as $t ) {
			$table = $wpdb->prefix . $t;

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE meta_key = %s", $key ) );

			if ( $rows > 0 ) {
				$deleted[ $t ] = $rows;
			}
		}

		if ( 0 === array_sum( $deleted ) ) {
			wp_send_json_success( __( 'No fields found.', 'meta-box-aio' ) );
		}

		$details = array_map(
			function ( $k, $v ) {
				return sprintf(
					// Translators: %1$d - total row, %2$s - table name
					__( '%1$d in %2$s', 'meta-box-aio' ),
					$v,
					$k
				);
			},
			array_keys( $deleted ),
			$deleted
		);

		wp_send_json_success(
			sprintf(
				// Translators: %s - details fields had removed
				_n( 'Successfully removed custom field: %s.', 'Successfully removed custom fields: %s.', array_sum( $deleted ), 'meta-box-aio' ),
				implode( ',', $details )
			)
		);
	}

	public function change_fields(): void {
		check_ajax_referer( 'mb_aio_tools_nonce' );

		global $wpdb;
		if ( empty( $_POST['original'] ) || empty( $_POST['new'] ) ) {
			wp_send_json_error( __( 'Please enter valid input values.', 'meta-box-aio' ) );
		}
		$original = sanitize_text_field( wp_unslash( $_POST['original'] ) );
		$new      = sanitize_text_field( wp_unslash( $_POST['new'] ) );

		$type = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'post' ) );
		if ( ! in_array( $type, [ 'post', 'term', 'user', 'all' ], true ) ) {
			wp_send_json_error( __( 'Invalid field type.', 'meta-box-aio' ) );
		}

		$tables = $this->get_tables( $type );

		$changed = [];
		foreach ( $tables as $t ) {
			$table = $wpdb->prefix . $t;

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->query( $wpdb->prepare( "UPDATE $table SET meta_key = %s WHERE meta_key = %s", $new, $original ) );

			if ( $rows > 0 ) {
				$changed[ $t ] = $rows;
			}
		}

		if ( 0 === array_sum( $changed ) ) {
			wp_send_json_success( __( 'No fields found.', 'meta-box-aio' ) );
		}

		$details = array_map(
			function ( $k, $v ) {
				return sprintf(
					// Translators: %1$d - total row, %2$s - table name
					__( '%1$d in %2$s', 'meta-box-aio' ),
					$v,
					$k
				);
			},
			array_keys( $changed ),
			$changed
		);

		wp_send_json_success(
			sprintf(
				// Translators: %s - details fields had changed
				_n( 'Successfully change custom field: %s.', 'Successfully change custom fields: %s.', array_sum( $changed ), 'meta-box-aio' ),
				implode( ',', $details )
			)
		);
	}

	public function change_post_type(): void {
		check_ajax_referer( 'mb_aio_tools_nonce' );

		global $wpdb;

		if ( empty( $_POST['original'] ) || empty( $_POST['new'] ) ) {
			wp_send_json_error( __( 'Please enter valid input values.', 'meta-box-aio' ) );
		}
		$original = sanitize_text_field( wp_unslash( $_POST['original'] ) );
		$new      = sanitize_text_field( wp_unslash( $_POST['new'] ) );

		if ( $original === $new ) {
			wp_send_json_error( __( 'Cannot convert same post type.', 'meta-box-aio' ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s", $new, $original ) );

		if ( 0 === $rows ) {
			wp_send_json_success( __( 'No posts with the original post type found.', 'meta-box-aio' ) );
		}

		wp_send_json_success(
			sprintf(
				// Translators: %d - total row
				_n( 'Successfully convert post type: %d post is updated.', 'Successfully convert post type: %d posts are updated.', $rows, 'meta-box-aio' ),
				$rows
			)
		);
	}

	private function get_tables( string $type ): array {
		$map = [
			'post' => [ 'postmeta' ],
			'term' => [ 'termmeta' ],
			'user' => [ 'usermeta' ],
			'all'  => [ 'postmeta', 'termmeta', 'usermeta' ],
		];
		return $map[ $type ];
	}
}
