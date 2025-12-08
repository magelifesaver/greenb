<?php
namespace MetaBox\CustomTable\Model;

use MetaBox\CustomTable\API;

class Admin {
	private $model;
	public $list_table;

	public function __construct( Model $model ) {
		$this->model = $model;

		if ( $this->model->show_in_menu ) {
			// Change priority to 11 to make sure all custom menus are generated.
			add_action( 'admin_menu', [ $this, 'add_menu' ], 11 );

			add_filter( 'set-screen-option', [ $this, 'set_screen_option' ], 10, 3 );
		}
	}

	public function add_menu(): void {
		$action = $this->action();
		$title  = $this->model->labels['all_items'];
		if ( $action === 'add' ) {
			$title = $this->model->labels['add_new_item'];
		} elseif ( $action === 'edit' ) {
			$title = $this->model->labels['edit_item'];
		}

		if ( $this->model->parent ) {
			$page = add_submenu_page(
				$this->model->parent,
				$title,
				$this->model->labels['menu_name'],
				$this->model->capability,
				"model-{$this->model->name}",
				[ $this, 'render' ]
			);
		} else {
			$page = add_menu_page(
				$title,
				$this->model->labels['menu_name'],
				$this->model->capability,
				"model-{$this->model->name}",
				[ $this, 'render' ],
				$this->model->menu_icon,
				$this->model->menu_position
			);
		}

		add_action( "load-$page", [ $this, 'add_body_class_hook' ] );
		add_action( "load-$page", [ $this, 'load_add_edit' ] );
		add_action( "load-$page", [ $this, 'load_list_table' ] );
		add_action( "admin_print_styles-$page", [ $this, 'enqueue' ] );
	}

	public function add_body_class_hook(): void {
		add_filter( 'admin_body_class', [ $this, 'add_body_class' ] );
	}

	public function add_body_class( string $body_classes ): string {
		$action  = $this->action() ?: 'list';
		$classes = [
			"model-{$this->model->name}",
			"model-$action",
		];

		// Make "add" screen has the same "edit" class.
		if ( $action === 'add' ) {
			$classes[] = 'model-edit';
		}

		return $body_classes . ' ' . implode( ' ', $classes );
	}

	public function load_add_edit(): void {
		if ( ! $this->is_screen_edit() ) {
			return;
		}

		add_meta_box(
			'submitdiv',
			__( 'Submit', 'mb-custom-table' ),
			[ $this, 'render_submit_box' ],
			null, // Current page.
			'side',
			'high'
		);

		do_action( 'mbct_model_edit_load', $this->model );

		// Save.
		if ( empty( $_POST['submit'] ) ) {
			return;
		}

		// Get the correct inserted ID when add new model.
		$object_id = rwmb_request()->filter_get( 'model-id', FILTER_SANITIZE_NUMBER_INT );
		$message   = 'updated';
		if ( 'add' === $this->action() ) {
			$object_id = $this->get_inserted_id();
			$message   = 'added';
		}

		$url = add_query_arg( [
			'model-action'  => 'edit',
			'model-id'      => $object_id,
			'model-message' => $message,
		] );
		wp_safe_redirect( $url );
	}

	public function render_submit_box(): void {
		$output = $this->template_submit_box();
		echo apply_filters( 'mbct_submit_box', $output, $this->model );
	}

	private function template_submit_box(): string {
		$delete_url = wp_nonce_url( add_query_arg( 'model-action', 'delete' ), 'delete' );

		// Get entry data for edit mode
		$entry = [];
		if ( $this->action() === 'edit' ) {
			$object_id = rwmb_request()->filter_get( 'model-id', FILTER_SANITIZE_NUMBER_INT );
			if ( $object_id ) {
				$entry = API::get( $object_id, $this->model->table );
			}
		}

		ob_start();
		?>
		<div id="submitpost" class="mbct-submit submitbox">
			<?php do_action( 'mbct_before_submit_box', $this->model ); ?>

			<?php if ( $this->model->supports( 'author' ) ) : ?>
				<div class="misc-pub-section mbct-author">
					<label><?php esc_html_e( 'Author', 'mb-custom-table' ); ?></label>
					<div class="mbct-info-value">
						<?php
						$current_author_id = 0;
						if ( $entry && ! empty( $entry['author'] ) ) {
							$current_author_id = $entry['author'];
						} elseif ( $this->action() === 'add' ) {
							$current_author_id = get_current_user_id();
						}

						wp_dropdown_users( [
							'name'             => 'mbct_author',
							'selected'         => $current_author_id,
							'include_selected' => true,
							'show'             => 'display_name_with_login',
						] );
						?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $entry && $this->model->supports( 'published_date' ) && ! empty( $entry['published_date'] ) ) : ?>
				<div class="misc-pub-section mbct-published-date">
					<div>
						<strong><?php esc_html_e( 'Published', 'mb-custom-table' ); ?></strong>
						<div class="mbct-info-value">
							<?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $entry['published_date'] ) ); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $entry && $this->model->supports( 'modified_date' ) && ! empty( $entry['modified_date'] ) ) : ?>
				<div class="misc-pub-section mbct-modified-date">
					<div>
						<strong><?php esc_html_e( 'Last Modified', 'mb-custom-table' ); ?></strong>
						<div class="mbct-info-value">
							<?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $entry['modified_date'] ) ); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div id="major-publishing-actions">
				<?php if ( $this->action() === 'edit' ) : ?>
					<div id="delete-action">
						<a class="submitdelete deletion" href="<?= esc_url( $delete_url ); ?>" id="mbct-delete"><?php esc_html_e( 'Delete', 'mb-custom-table' ) ?></a>
					</div>
				<?php endif ?>
				<div id="publishing-action">
					<?php submit_button( __( 'Save', 'mb-custom-table' ), 'primary', 'submit', false ); ?>
				</div>
				<div class="clear"></div>
			</div>

			<?php do_action( 'mbct_after_submit_box', $this->model ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private function get_inserted_id() {
		global $wpdb;
		return $wpdb->insert_id;
	}

	public function load_list_table() {
		if ( ! $this->is_screen_list() ) {
			return;
		}

		$this->handle_delete();

		$args             = [
			'model' => $this->model,
		];
		$list_table       = new ListTable( $args );
		$this->list_table = apply_filters( 'mbct_list_table_object', $list_table, $args );

		$args = [
			'label'   => __( 'Number of lines per page', 'mb-custom-table' ),
			'default' => 20,
			'option'  => "{$this->model->name}_per_page",
		];
		add_screen_option( 'per_page', $args );

		// For admin columns extension to handle actions.
		do_action( "mbct_{$this->model->name}_list_table_load" );
	}

	private function handle_delete() {
		if ( $this->action() !== 'delete' ) {
			return;
		}
		check_admin_referer( 'delete' );
		$id = rwmb_request()->filter_get( 'model-id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id ) {
			return;
		}

		global $wpdb;
		do_action( 'mbct_before_delete', $id, $this->model->table );
		$wpdb->delete(
			$this->model->table,
			[ 'ID' => $id ],
			[ '%d' ]
		);
		do_action( 'mbct_after_delete', $id, $this->model->table );

		$url = remove_query_arg( [ 'model-action', 'model-id', '_wpnonce' ] );
		$url = add_query_arg( [
			'model-message' => 'deleted',
		], $url );

		wp_safe_redirect( $url );
	}

	public function set_screen_option( $status, $option, $value ) {
		return "{$this->model->name}_per_page" === $option ? $value : $status;
	}

	public function enqueue() {
		if ( $this->is_screen_edit() ) {
			wp_enqueue_style( 'mbct-model-edit', MBCT_URL . 'assets/edit.css', [], filemtime( MBCT_DIR . '/assets/edit.css' ) );
			wp_enqueue_script( 'mbct-model-edit', MBCT_URL . 'assets/edit.js', [], filemtime( MBCT_DIR . '/assets/edit.js' ), true );
			wp_localize_script( 'mbct-model-edit', 'Mbct', [
				'confirm' => __( 'Are you sure you want to delete? This action cannot be undone.', 'mb-custom-table' ),
			] );
			return;
		}

		wp_enqueue_style( 'mbct-list-table', MBCT_URL . 'assets/list-table.css', [], filemtime( MBCT_DIR . '/assets/list-table.css' ) );
		wp_enqueue_script( 'mbct-list-table', MBCT_URL . 'assets/list-table.js', [ 'jquery' ], filemtime( MBCT_DIR . '/assets/list-table.js' ), true );
		wp_localize_script( 'mbct-list-table', 'MbctListTable', [
			'nonceBulkActions' => wp_create_nonce( 'bulk-actions' ),
			'confirm'          => __( 'Are you sure you want to delete? This action cannot be undone.', 'mb-custom-table' ),
		] );
	}

	public function render() {
		$action = $this->action();
		$view   = in_array( $action, [ 'add', 'edit' ] ) ? $action : 'list-table';
		include MBCT_DIR . "/views/$view.php";
	}

	private function is_screen_edit() {
		return in_array( $this->action(), [ 'add', 'edit' ], true );
	}

	private function is_screen_list() {
		return ! $this->is_screen_edit();
	}

	private function action() {
		return rwmb_request()->get( 'model-action' );
	}
}
