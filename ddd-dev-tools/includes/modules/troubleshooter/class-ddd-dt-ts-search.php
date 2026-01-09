<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

require_once DDD_DT_DIR . 'includes/modules/troubleshooter/search/helpers.php';
require_once DDD_DT_DIR . 'includes/modules/troubleshooter/search/class-ddd-dt-ts-resolver.php';
require_once DDD_DT_DIR . 'includes/modules/troubleshooter/search/class-ddd-dt-ts-engine-php.php';
require_once DDD_DT_DIR . 'includes/modules/troubleshooter/search/class-ddd-dt-ts-engine-grep.php';
require_once DDD_DT_DIR . 'includes/modules/troubleshooter/search/class-ddd-dt-ts-engine-rg.php';

class DDD_DT_TS_Search {
    public static function available_engines(): array {
        return [
            'php'  => [ 'label' => 'PHP (portable)', 'available' => true ],
            'grep' => [ 'label' => 'grep (fast)', 'available' => ddd_dt_ts_command_exists( 'grep' ) && ddd_dt_ts_is_proc_open_enabled() ],
            'rg'   => [ 'label' => 'ripgrep (rg, fastest)', 'available' => ddd_dt_ts_command_exists( 'rg' ) && ddd_dt_ts_is_proc_open_enabled() ],
        ];
    }

    public static function ui_defaults(): array {
        return [
            'extensions'   => 'php,js,css,scss,json,md,txt,xml,yml,yaml,twig',
            'exclude_dirs' => 'vendor,node_modules,.git,dist,build,cache,logs,uploads',
            'max_results'  => 200,
            'max_file_kb'  => 1024,
            'max_ms'       => 8000,
        ];
    }

    public static function run( array $args ): array {
        $scope = DDD_DT_TS_Resolver::normalize_scope( $args['scope'] ?? 'plugin' );
        $plugin = (string) ( $args['plugin'] ?? '' );
        $mu = (string) ( $args['mu_plugin'] ?? '' );
        $mode = ( $args['mode'] ?? '' ) === 'filename' ? 'filename' : 'content';
        $engine_requested = sanitize_key( (string) ( $args['engine'] ?? 'php' ) );

        $resolved = DDD_DT_TS_Resolver::resolve_roots( $scope, $plugin, $mu );
        if ( ! $resolved['ok'] ) {
            return [ 'ok' => false, 'error' => $resolved['error'] ];
        }

        $engine_used = ( $mode === 'filename' ) ? 'php' : $engine_requested;
        $avail = self::available_engines();
        if ( ! isset( $avail[ $engine_used ] ) || empty( $avail[ $engine_used ]['available'] ) ) {
            $engine_used = 'php';
        }

        $args['mode'] = $mode;
        $args['max_ms'] = max( 1000, min( 20000, absint( $args['max_ms'] ?? self::ui_defaults()['max_ms'] ) ) );

        switch ( $engine_used ) {
            case 'rg':
                $result = DDD_DT_TS_Engine_Rg::search( $resolved['roots'], $args );
                break;
            case 'grep':
                $result = DDD_DT_TS_Engine_Grep::search( $resolved['roots'], $args );
                break;
            case 'php':
            default:
                $result = DDD_DT_TS_Engine_PHP::search( $resolved['roots'], $args );
                break;
        }

        if ( empty( $result['ok'] ) ) {
            return $result;
        }

        $result['meta']['scope'] = $scope;
        $result['meta']['engine_requested'] = $engine_requested;
        $result['meta']['engine_used'] = $engine_used;
        return $result;
    }
}
