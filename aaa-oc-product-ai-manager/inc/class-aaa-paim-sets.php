<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/inc/class-aaa-paim-sets.php
 * Purpose: CRUD for attribute sets and their items (attributes/taxonomies/meta).
 * Version: 0.3.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_PAIM_DEBUG_SETS' ) ) { define( 'AAA_PAIM_DEBUG_SETS', true ); }

class AAA_Paim_Sets {

	public static function create_set( string $name, int $category_term_id, array $items, string $notes = '' ) : int {
		global $wpdb;
		$sets_table  = $wpdb->prefix . 'aaa_paim_attribute_sets';
		$attrs_table = $wpdb->prefix . 'aaa_paim_set_attributes';
		$now = current_time( 'mysql' );
		$uid = get_current_user_id();

		$wpdb->insert( $sets_table, [
			'set_name'        => sanitize_text_field( $name ),
			'category_term_id'=> absint( $category_term_id ),
			'status'          => 'active',
			'notes'           => $notes,
			'created_at'      => $now,
			'updated_at'      => $now,
			'updated_by'      => $uid,
		] );
		$set_id = (int) $wpdb->insert_id;

		self::replace_items( $set_id, $items, $now );
		return $set_id;
	}

	public static function update_set( int $set_id, string $name, int $category_term_id, array $items, string $notes = '' ) : void {
		global $wpdb;
		$sets_table = $wpdb->prefix . 'aaa_paim_attribute_sets';
		$now = current_time( 'mysql' );
		$uid = get_current_user_id();

		$wpdb->update( $sets_table, [
			'set_name'        => sanitize_text_field( $name ),
			'category_term_id'=> absint( $category_term_id ),
			'notes'           => $notes,
			'updated_at'      => $now,
			'updated_by'      => $uid,
		], [ 'id' => $set_id ], [ '%s','%d','%s','%s','%d' ], [ '%d' ] );

		self::replace_items( $set_id, $items, $now );
	}

	private static function replace_items( int $set_id, array $items, string $now ) : void {
		global $wpdb;
		$attrs_table = $wpdb->prefix . 'aaa_paim_set_attributes';

		// wipe + re-add (small table; simpler and safer than diff)
		$wpdb->delete( $attrs_table, [ 'set_id' => $set_id ], [ '%d' ] );

		$order = 0;
		foreach ( $items as $item ) {
			$object_type = $item['type']; // taxonomy | meta
			$object_key  = $item['key'];
			$label       = $item['label'] ?? '';
			$wpdb->insert( $attrs_table, [
				'set_id'      => $set_id,
				'object_type' => sanitize_key( $object_type ),
				'object_key'  => sanitize_key( $object_key ),
				'label'       => sanitize_text_field( $label ),
				'ui_order'    => $order++,
				'created_at'  => $now,
			] );
		}
		aaa_paim_log( [ 'set_items_replaced' => $set_id, 'count' => count( $items ) ], 'SETS' );
	}

	public static function get_set( int $set_id ) : ?array {
		global $wpdb;
		$t = $wpdb->prefix . 'aaa_paim_attribute_sets';
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id=%d", $set_id ), ARRAY_A );
		return $row ?: null;
	}

	public static function get_set_items( int $set_id ) : array {
		global $wpdb;
		$t = $wpdb->prefix . 'aaa_paim_set_attributes';
		$sql = $wpdb->prepare( "SELECT object_type, object_key, label FROM {$t} WHERE set_id=%d ORDER BY ui_order ASC, id ASC", $set_id );
		return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
	}

	public static function list_sets() : array {
		global $wpdb;
		$sets  = $wpdb->prefix . 'aaa_paim_attribute_sets';
		$attrs = $wpdb->prefix . 'aaa_paim_set_attributes';
		$sql = "
			SELECT s.id, s.set_name, s.category_term_id, s.updated_at,
				   (SELECT COUNT(*) FROM {$attrs} a WHERE a.set_id = s.id) AS attr_count
			FROM {$sets} s
			ORDER BY s.updated_at DESC
			LIMIT 100
		";
		return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
	}

	public static function delete_set( int $set_id ) : void {
		global $wpdb;
		$sets  = $wpdb->prefix . 'aaa_paim_attribute_sets';
		$attrs = $wpdb->prefix . 'aaa_paim_set_attributes';
		$wpdb->delete( $sets,  [ 'id' => $set_id ],  [ '%d' ] );
		$wpdb->delete( $attrs, [ 'set_id' => $set_id ], [ '%d' ] );
		aaa_paim_log( [ 'deleted_set_id' => $set_id ], 'SETS' );
	}
}
