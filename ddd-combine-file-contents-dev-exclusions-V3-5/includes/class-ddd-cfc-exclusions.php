<?php
/**
 * File Path: includes/class-ddd-cfc-exclusions.php
 * Purpose: Manage plugin/folder exclusions for indexing and search.
 */

defined('ABSPATH' ) || exit;

class DDD_CFC_Exclusions {

    /**
     * Retrieve the current exclusions array from options or default list.
     *
     * @return array List of plugin folder slugs to exclude.
     */
    public static function get_list() {
        $default = [
        ];
        $saved = get_option('cfc_ls_exclusions', [] );
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }
        return array_unique( array_merge( $default, $saved ) );
    }

    /**
     * Save a new exclusions list.
     *
     * @param array $list List of folder slugs.
     */
    public static function set_list( array $list ) {
        update_option('cfc_ls_exclusions', array_values( array_unique( $list ) ) );
    }

    /**
     * Hook into the indexer to filter out excluded slugs when scanning.
     * Should be called before build/sync runs.
     *
     * @param array $entries Indexed entries (plugin_slug, path, is_dir).
     * @return array Filtered entries.
     */
    public static function filter_entries( array $entries ) {
        $ex = self::get_list();
        return array_filter( $entries, function( $row ) use ( $ex ) {
            return ! in_array( $row['plugin_slug'], $ex, true );
        } );
    }

}
