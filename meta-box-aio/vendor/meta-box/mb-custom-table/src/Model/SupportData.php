<?php
namespace MetaBox\CustomTable\Model;

use MetaBox\CustomTable\API;

class SupportData {
	private Model $model;

	public function __construct( Model $model ) {
		$this->model = $model;

		add_filter( 'mbct_add_data', [ $this, 'add'], 10, 3 );
		add_filter( 'mbct_update_data', [ $this, 'update'], 10, 3 );
	}

	public function add( array $row, ?int $object_id, string $table ): array {
		if ( $this->model->table !== $table ) {
			return $row;
		}

		$data = [];

		if ( $this->model->supports( 'author' ) ) {
			$data['author'] = get_current_user_id();
		}

		$current_time = current_time( 'mysql' );
		if ( $this->model->supports( 'published_date' ) ) {
			$data['published_date'] = $current_time;
		}
		if ( $this->model->supports( 'modified_date' ) ) {
			$data['modified_date'] = $current_time;
		}

		return array_merge( $row, $data );
	}

	public function update( array $row, int $object_id, string $table ): array {
		if ( $this->model->table !== $table ) {
			return $row;
		}

		$data = [];

		// Only update modified_date on update.
		// Author and published_date should remain unchanged if they already exist.
		$current_time = current_time( 'mysql' );
		if ( $this->model->supports( 'modified_date' ) ) {
			$data['modified_date'] = $current_time;
		}

		// If model doesn't have author and published_date, add them.
		$entry = API::get( $object_id, $table );
		if ( $this->model->supports( 'author' ) && empty( $entry['author'] ) ) {
			$data['author'] = get_current_user_id();
		}
		if ( $this->model->supports( 'published_date' ) && empty( $entry['published_date'] ) ) {
			$data['published_date'] = current_time( 'mysql' );
		}

		// If new author is submitted.
		$new_author_id = rwmb_request()->filter_post( 'mbct_author', FILTER_SANITIZE_NUMBER_INT );
		if ( $this->model->supports( 'author' ) && $new_author_id ) {
			$data['author'] = $new_author_id;
		}

		return array_merge( $row, $data );
	}
}