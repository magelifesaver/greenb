<?php
/**
 * File Path: includes/class-ddd-cfc-indexer.php
 * Purpose: Encapsulate all index-building, syncing, and clearing logic.
 */

defined( 'ABSPATH' ) || exit;

class DDD_CFC_Indexer {

    /**
     * Build the full index from scratch.
     *
     * @return array{count:int}
     */
    public static function build_all() {
        global $wpdb;

        $table = $wpdb->prefix . 'ls_file_index';

        // 1) Ensure table exists.
        $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
        if ( $exists !== $table && class_exists( 'DDD_CFC_Table_Installer' ) ) {
            DDD_CFC_Table_Installer::create_table();
        }

        // 2) Clear existing entries.
        $wpdb->query( "TRUNCATE TABLE {$table}" );

        // 3) Gather all filesystem entries from configured roots.
        $entries = array();
        foreach ( self::get_scan_roots() as $root ) {
            $base   = $root['base'];   // absolute path
            $prefix = $root['prefix']; // relative prefix ('' or 'mu-plugins')

            if ( ! is_dir( $base ) ) {
                continue;
            }

            $items = @scandir( $base );
            if ( ! $items ) {
                continue;
            }

            foreach ( $items as $slug ) {
                if ( '.' === $slug || '..' === $slug ) {
                    continue;
                }

                $full = $base . '/' . $slug;
                $rel  = ( '' === $prefix ) ? $slug : $prefix . '/' . $slug;

                if ( is_dir( $full ) ) {
                    $entries[] = array(
                        'plugin_slug' => strtok( $rel, '/' ),
                        'path'        => $rel,
                        'is_dir'      => 1,
                    );
                    self::gather_entries( $full, $rel, $entries );
                } elseif ( is_file( $full ) ) {
                    $entries[] = array(
                        'plugin_slug' => strtok( $rel, '/' ),
                        'path'        => $rel,
                        'is_dir'      => 0,
                    );
                }
            }
        }

        // 4) Apply plugin exclusions (by slug).
        if ( class_exists( 'DDD_CFC_Exclusions' ) ) {
            $entries = DDD_CFC_Exclusions::filter_entries( $entries );
        }

        // 5) Bulk insert.
        $inserted = 0;
        foreach ( $entries as $row ) {
            $wpdb->insert(
                $table,
                array(
                    'plugin_slug' => $row['plugin_slug'],
                    'path'        => $row['path'],
                    'is_dir'      => (int) $row['is_dir'],
                ),
                array( '%s', '%s', '%d' )
            );
            $inserted++;
        }

        update_option( 'cfc_ls_last_index_time', time() );

        return array(
            'count' => $inserted,
        );
    }

    /**
     * Determine which roots to scan.
     * - Always scans the main plugins directory (via CFC_PLUGINS_BASE_DIR or wp-content/plugins).
     * - Optionally scans MU-plugins when cfc_include_mu_plugins = 'yes'.
     *
     * @return array<int,array{base:string,prefix:string}>
     */
    protected static function get_scan_roots() {
        $roots = array();

        // Normal plugins base.
        if ( defined( 'CFC_PLUGINS_BASE_DIR' ) && is_dir( CFC_PLUGINS_BASE_DIR ) ) {
            $roots[] = array(
                'base'   => CFC_PLUGINS_BASE_DIR,
                'prefix' => '',
            );
        } else {
            $base = WP_CONTENT_DIR . '/plugins';
            if ( is_dir( $base ) ) {
                $roots[] = array(
                    'base'   => $base,
                    'prefix' => '',
                );
            }
        }

        // Optional MU-plugins base.
        $include_mu = get_option( 'cfc_include_mu_plugins', 'no' );
        if ( 'yes' === $include_mu && defined( 'WPMU_PLUGIN_DIR' ) && is_dir( WPMU_PLUGIN_DIR ) ) {
            // Stored paths will start with "mu-plugins/..."
            $roots[] = array(
                'base'   => WPMU_PLUGIN_DIR,
                'prefix' => 'mu-plugins',
            );
        }

        return $roots;
    }

    /**
     * Sync index with current filesystem.
     *
     * @return array{count:int,added:int,removed:int}
     */
    public static function sync() {
        global $wpdb;

        $table  = $wpdb->prefix . 'ls_file_index';
        $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
        if ( $exists !== $table ) {
            // Nothing to sync yet.
            return array(
                'count'   => 0,
                'added'   => 0,
                'removed' => 0,
            );
        }

        // DB paths: map path => id.
        $rows     = $wpdb->get_results( "SELECT id, path FROM {$table}", ARRAY_A );
        $db_paths = array();
        foreach ( $rows as $row ) {
            $db_paths[ $row['path'] ] = (int) $row['id'];
        }

        // Filesystem entries from all roots.
        $fs_entries = array();
        foreach ( self::get_scan_roots() as $root ) {
            $base   = $root['base'];
            $prefix = $root['prefix'];

            if ( ! is_dir( $base ) ) {
                continue;
            }

            $items = @scandir( $base );
            if ( ! $items ) {
                continue;
            }

            foreach ( $items as $slug ) {
                if ( '.' === $slug || '..' === $slug ) {
                    continue;
                }

                $full = $base . '/' . $slug;
                $rel  = ( '' === $prefix ) ? $slug : $prefix . '/' . $slug;

                if ( is_dir( $full ) ) {
                    $fs_entries[] = array(
                        'plugin_slug' => strtok( $rel, '/' ),
                        'path'        => $rel,
                        'is_dir'      => 1,
                    );
                    self::gather_entries( $full, $rel, $fs_entries );
                } elseif ( is_file( $full ) ) {
                    $fs_entries[] = array(
                        'plugin_slug' => strtok( $rel, '/' ),
                        'path'        => $rel,
                        'is_dir'      => 0,
                    );
                }
            }
        }

        // Apply exclusions to FS side.
        if ( class_exists( 'DDD_CFC_Exclusions' ) ) {
            $fs_entries = DDD_CFC_Exclusions::filter_entries( $fs_entries );
        }

        // Map FS path => row.
        $fs_map = array();
        foreach ( $fs_entries as $row ) {
            $fs_map[ $row['path'] ] = $row;
        }

        $db_paths_list = array_keys( $db_paths );
        $fs_paths_list = array_keys( $fs_map );

        $to_remove_paths = array_diff( $db_paths_list, $fs_paths_list );
        $to_add_paths    = array_diff( $fs_paths_list, $db_paths_list );

        // Remove rows that no longer exist on disk.
        $removed = 0;
        if ( $to_remove_paths ) {
            $ids = array();
            foreach ( $to_remove_paths as $path ) {
                if ( isset( $db_paths[ $path ] ) ) {
                    $ids[] = (int) $db_paths[ $path ];
                }
            }
            if ( $ids ) {
                $in = implode( ',', array_map( 'intval', $ids ) );
                $wpdb->query( "DELETE FROM {$table} WHERE id IN ({$in})" );
                $removed = count( $ids );
            }
        }

        // Add new rows.
        $added = 0;
        foreach ( $to_add_paths as $path ) {
            $row = $fs_map[ $path ];
            $wpdb->insert(
                $table,
                array(
                    'plugin_slug' => $row['plugin_slug'],
                    'path'        => $row['path'],
                    'is_dir'      => (int) $row['is_dir'],
                ),
                array( '%s', '%s', '%d' )
            );
            $added++;
        }

        update_option( 'cfc_ls_last_index_time', time() );
        $new_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

        return array(
            'count'   => $new_count,
            'added'   => $added,
            'removed' => $removed,
        );
    }

    /**
     * Clear the index table.
     *
     * @return array{count:int}
     */
    public static function clear() {
        global $wpdb;

        $table = $wpdb->prefix . 'ls_file_index';
        $wpdb->query( "TRUNCATE TABLE {$table}" );
        update_option( 'cfc_ls_last_index_time', time() );

        return array( 'count' => 0 );
    }

    /**
     * Recursive helper to gather directory entries below a given relative path.
     *
     * @param string $dir Absolute directory path.
     * @param string $rel Relative path from scan root (e.g. "plugin-slug/subdir").
     * @param array  $out Accumulator array (by reference).
     */
    protected static function gather_entries( $dir, $rel, array &$out ) {
        $items = @scandir( $dir );
        if ( ! $items ) {
            return;
        }

        $dirs  = array();
        $files = array();

        foreach ( $items as $item ) {
            if ( '.' === $item || '..' === $item ) {
                continue;
            }
            $full = $dir . '/' . $item;
            if ( is_dir( $full ) ) {
                $dirs[] = $item;
            } elseif ( is_file( $full ) ) {
                $files[] = $item;
            }
        }

        sort( $dirs, SORT_NATURAL | SORT_FLAG_CASE );
        sort( $files, SORT_NATURAL | SORT_FLAG_CASE );

        // Folders
        foreach ( $dirs as $d ) {
            $path = $rel . '/' . $d;
            $out[] = array(
                'plugin_slug' => strtok( $rel, '/' ),
                'path'        => $path,
                'is_dir'      => 1,
            );
            self::gather_entries( $dir . '/' . $d, $path, $out );
        }

        // Files
        foreach ( $files as $f ) {
            $out[] = array(
                'plugin_slug' => strtok( $rel, '/' ),
                'path'        => $rel . '/' . $f,
                'is_dir'      => 0,
            );
        }
    }
}
