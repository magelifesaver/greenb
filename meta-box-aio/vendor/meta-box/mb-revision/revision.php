<?php
/**
 * Handle the revision meta data when saving or restore posts.
 *
 * @package    Meta Box
 * @subpackage MB Revision
 */
class MB_Revision {
	public function __construct() {
		// Add fields to revision
		add_filter( '_wp_post_revision_fields', [ $this, 'revision_fields' ], 10, 2 );

		// When saving a post, copy meta data to revision.
		add_action( '_wp_put_post_revision', [ $this, 'copy_fields_to_revision' ], 10, 2 );

		// Copy revision meta data to parent post and recent created revision.
		add_action( 'wp_restore_post_revision', [ $this, 'restore_revision' ], 10, 2 );

		// Force to save revision when post is saved.
		add_filter( 'wp_save_post_revision_check_for_changes', [ $this, 'check_for_changes' ] );
		add_filter( 'wp_save_post_revision_post_has_changed', [ $this, 'post_has_changed' ], 10, 3 );
	}

	/**
	 * Don't save revision when in revision screen or restoring revision.
	 *
	 * @return bool
	 */
	public function check_for_changes( $is_changed ): bool {
		if ( $this->in_revision_screen() ) {
			return false;
		}

		return $is_changed;
	}

	public function get_last_revision_id( int $post_id ): ?int {
		$last_and_total = wp_get_latest_revision_id_and_total_count( $post_id );

		if ( is_wp_error( $last_and_total ) ) {
			return null;
		}

		return $last_and_total['latest_id'];
	}

	public function copy_fields_to_revision( $revision_id, $post_id ) {
		foreach ( $this->get_fields() as $field ) {
			$this->copy_value( $post_id, $revision_id, $field );
		}

		$this->update_data_for_custom_table( $revision_id );
	}

	public function restore_revision( $post_id, $revision_id ) {
		foreach ( $this->get_fields() as $field ) {
			$this->copy_value( $revision_id, $post_id, $field );
			$this->copy_value( $revision_id, $this->get_last_revision_id( $post_id ), $field );
		}

		$this->update_data_for_custom_table( $post_id );
		$this->update_data_for_custom_table( $this->get_last_revision_id( $post_id ) );
	}

	/**
	 * Check if we're in revision screen or we are restoring a revision.
	 */
	private function in_revision_screen(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'get-revision-diffs' ) {
			return true;
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'restore' ) {
			return true;
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$current_screen = get_current_screen();

		if ( ! $current_screen || 'revision' !== $current_screen->base ) {
			return false;
		}

		return true;
	}

	/**
	 * Add meta box fields to revision screen.
	 *
	 * @param  array $fields Displayed fields.
	 * @return array
	 */
	public function revision_fields( $fields ) {
		if ( ! $this->in_revision_screen() ) {
			return $fields;
		}

		foreach ( $this->get_fields() as $field ) {
			$fields[ $field['id'] ] = $field['name'];

			add_filter( '_wp_post_revision_field_' . $field['id'], function ( $value, $field_id, $compare_to ) use ( $field ) {
				$value = $this->get_field_value_for_comparison( $value, $field, $compare_to );

				return $value;
			}, 10, 3 );
		}

		return $fields;
	}

	/**
	 * When saving a post, doing additional check to see if any custom fields has changed.
	 *
	 * @param  bool $is_changed Is post changed.
	 */
	public function post_has_changed( $is_changed, $revision, $post ) {
		// No need to check if no fields submitted.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( empty( $_POST ) ) {
			return false;
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Because WP sends several requests to make revisions, and when the first revision is created,
		// the meta box fields are not saved yet so we need to manually copy them to the revision.
		// and stop the next request to avoid creating duplicated revisions.
		$count = wp_get_latest_revision_id_and_total_count( $post->ID );
		if ( $count['count'] === 1 ) {
			$filled = get_post_meta( $revision->ID, '_filled', true );

			if ( ! $filled ) {
				$this->copy_fields_to_revision( $revision->ID, $post->ID );
				update_metadata( 'post', $revision->ID, '_filled', true );
				return false;
			}
		}

		foreach ( $this->get_fields() as $field ) {
			$submitted = $this->get_submitted_value( $field );
			$storage   = $this->get_field_storage( $field );
			$single    = $field['clone'] || ! $field['multiple'];
			$stored    = $storage->get( $revision->ID, $field['id'], [ 'single' => $single ] );

			if ( $submitted !== $stored ) {
				return true;
			}
		}

		return $is_changed;
	}

	/**
	 * Get field value to show on the revision comparison screen.
	 *
	 * @param  string  $value Field data.
	 * @param  string  $field Field id.
	 * @param  WP_Post $post  Post object.
	 * @return string
	 */
	public function get_field_value_for_comparison( $value, $field, $post ) {
		$single  = $field['clone'] || ! $field['multiple'];
		$storage = $this->get_field_storage( $field );
		$value   = $storage->get( $post->ID, $field['id'], [ 'single' => $single ] );

		$value = $this->transform_choice_value_to_label( $value, $field );
		$value = $this->transform_object_value_to_label( $value, $field );

		$value = is_array( $value ) ? wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) : (string) $value;

		return $value;
	}

	private function get_fields(): array {
		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'post' ] );
		$meta_boxes = array_filter( $meta_boxes, [ $this, 'supports_revisions' ] );

		$fields = [];
		foreach ( $meta_boxes as $meta_box ) {
			$fields = array_merge( $fields, $meta_box->fields );
		}

		$fields = array_filter( $fields, [ $this, 'has_value' ] );

		return $fields;
	}

	private function supports_revisions( $meta_box ): bool {
		return (bool) $meta_box->revision;
	}

	private function has_value( $field ) {
		$types = [ 'heading', 'custom_html', 'divider', 'button' ];
		return ! empty( $field['id'] ) && ! in_array( $field['type'], $types, true );
	}

	private function transform_choice_value_to_label( $value, $field ) {
		$types = [ 'checkbox_list', 'radio', 'select', 'select_advanced' ];
		if ( ! in_array( $field['type'], $types, true ) ) {
			return $value;
		}
		$options = $field['options'];

		return $this->get_option_label( $value, $options );
	}

	private function transform_object_value_to_label( $value, $field ) {
		$types = [ 'taxonomy', 'taxonomy_advanced', 'post', 'user' ];
		if ( ! in_array( $field['type'], $types, true ) ) {
			return $value;
		}
		$options = RWMB_Field::call( $field, 'query', $value );
		$options = RWMB_Choice_Field::transform_options( $options );
		$options = wp_list_pluck( $options, 'label', 'value' );

		return $this->get_option_label( $value, $options );
	}

	private function get_option_label( $value, $options ) {
		if ( is_array( $value ) ) {
			array_walk_recursive( $value, [ $this, 'get_single_option_label' ], $options );
		} else {
			$this->get_single_option_label( $value, null, $options );
		}
		return $value;
	}

	private function get_single_option_label( &$value, $key, $options ) {
		$value = $options[ $value ] ?? $value;
	}

	private function get_submitted_value( $field ) {
		$single  = $field['clone'] || ! $field['multiple'];
		$default = $single ? '' : [];

		return rwmb_request()->post( $field['id'], $default );
	}

	/**
	 * Copy meta value from post to another post.
	 *
	 * @param  int   $from_id From post ID.
	 * @param  int   $to_id   To post ID.
	 * @param  array $field   Field data.
	 */
	private function copy_value( $from_id, $to_id, $field ) {
		$single   = $field['clone'] || ! $field['multiple'];
		$meta_key = $field['id'];
		$storage  = $this->get_field_storage( $field );

		$value = $storage->get( $from_id, $meta_key, [ 'single' => $single ] );

		if ( $single ) {
			if ( ! empty( $value ) ) {
				$storage->update( $to_id, $meta_key, $value );
				return;
			}

			$storage->delete( $to_id, $meta_key );
			return;
		}

		if ( ! empty( $value ) ) {
			$storage->delete( $to_id, $meta_key );
			foreach ( $value as $v ) {
				$storage->add( $to_id, $meta_key, $v );
			}
			return;
		}

		$storage->delete( $to_id, $meta_key );
	}

	private function get_field_storage( $field ) {
		return $field['storage'] ?? rwmb_get_storage( 'post' );
	}

	private function update_data_for_custom_table( $object_id ) {
		if ( class_exists( 'MetaBox\CustomTable\Loader' ) && method_exists( 'MetaBox\CustomTable\Loader', 'update_object_data' ) ) {
			$loader = new MetaBox\CustomTable\Loader();
			$loader->update_object_data( $object_id );
		}
	}
}
