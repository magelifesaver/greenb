<?php
namespace MetaBox\CustomTable\Model;

class TableSchema {
	private $model;

	public function __construct( Model $model ) {
		$this->model = $model;

		add_filter( 'mbct_table_schema', [ $this, 'modify_table_schema' ], 10, 3 );
	}

	public function modify_table_schema( string $table, array &$columns, array &$keys ): void {
		if ( $this->model->table !== $table ) {
			return;
		}

		// Set ID column auto-increment.
		$columns['ID'] .= ' AUTO_INCREMENT';

		$this->add_supports_columns( $columns );
		$this->add_supports_keys( $keys );
	}

	private function add_supports_columns( array &$columns ): void {
		if ( $this->model->supports( 'author' ) ) {
			$columns['author'] = 'BIGINT(20) UNSIGNED';
		}
		if ( $this->model->supports( 'published_date' ) ) {
			$columns['published_date'] = 'DATETIME';
		}
		if ( $this->model->supports( 'modified_date' ) ) {
			$columns['modified_date'] = 'DATETIME';
		}
	}

	private function add_supports_keys( array &$keys ): void {
		if ( $this->model->supports( 'author' ) ) {
			$keys[] = 'author';
		}
	}
}
