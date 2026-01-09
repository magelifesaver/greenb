<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

require_once DDD_DT_DIR . 'includes/modules/troubleshooter/search/helpers.php';

class DDD_DT_TS_Targets {
    public static function get_admin_view_data(): array {
        return [
            'plugins'    => self::get_plugins_grouped(),
            'mu_plugins' => self::get_mu_plugins_list(),
        ];
    }

    private static function ensure_plugins_api() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }

    public static function get_plugins_grouped(): array {
        self::ensure_plugins_api();

        $all = (array) get_plugins();
        $active = (array) get_option( 'active_plugins', [] );
        $sitewide = is_multisite() ? array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) ) : [];
        $active_set = array_fill_keys( array_merge( $active, $sitewide ), true );

        $groups = [ 'active' => [], 'inactive' => [] ];
        foreach ( $all as $file => $data ) {
            $name = isset( $data['Name'] ) ? (string) $data['Name'] : (string) $file;
            $group = isset( $active_set[ $file ] ) ? 'active' : 'inactive';
            $groups[ $group ][] = [ 'file' => $file, 'name' => $name ];
        }

        foreach ( $groups as $k => $items ) {
            usort(
                $items,
                function( $a, $b ) {
                    return strcasecmp( (string) $a['name'], (string) $b['name'] );
                }
            );
            $groups[ $k ] = $items;
        }

        return $groups;
    }

    public static function get_mu_plugins_list(): array {
        $dir = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins';
        $dir = ddd_dt_ts_realpath( $dir );
        if ( $dir === '' || ! is_dir( $dir ) ) {
            return [];
        }

        $files = [];
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
        );
        foreach ( $it as $f ) {
            if ( $f->isFile() && strtolower( $f->getExtension() ) === 'php' ) {
                $files[] = ddd_dt_ts_relpath_from_content( $f->getPathname() );
            }
            if ( count( $files ) >= 500 ) {
                break;
            }
        }
        sort( $files, SORT_NATURAL | SORT_FLAG_CASE );
        return $files;
    }
}
