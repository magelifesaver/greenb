<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

function ddd_dt_ts_is_shell_exec_enabled(): bool {
    if ( ! function_exists( 'shell_exec' ) ) {
        return false;
    }
    $disabled = (string) ini_get( 'disable_functions' );
    return ( stripos( $disabled, 'shell_exec' ) === false );
}

function ddd_dt_ts_is_proc_open_enabled(): bool {
    if ( ! function_exists( 'proc_open' ) ) {
        return false;
    }
    $disabled = (string) ini_get( 'disable_functions' );
    return ( stripos( $disabled, 'proc_open' ) === false );
}

function ddd_dt_ts_command_exists( string $command ): bool {
    $command = sanitize_key( (string) $command );
    if ( $command === '' || ! ddd_dt_ts_is_shell_exec_enabled() ) {
        return false;
    }
    $out = shell_exec( 'command -v ' . escapeshellarg( $command ) . ' 2>/dev/null' );
    return is_string( $out ) && trim( $out ) !== '';
}

function ddd_dt_ts_bool_from_post( string $key, bool $default = false ): bool {
    if ( ! isset( $_POST[ $key ] ) ) {
        return $default;
    }
    $val = wp_unslash( $_POST[ $key ] );
    return $val === '1' || $val === 'true' || $val === 1 || $val === true;
}

function ddd_dt_ts_csv_list( $raw, int $max = 25 ): array {
    $raw = is_string( $raw ) ? $raw : '';
    $raw = trim( sanitize_text_field( $raw ) );
    if ( $raw === '' ) {
        return [];
    }
    $parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
    $parts = array_slice( $parts, 0, max( 1, $max ) );
    return array_values( array_unique( $parts ) );
}

function ddd_dt_ts_extensions_from_csv( $raw ): array {
    $items = ddd_dt_ts_csv_list( $raw, 50 );
    $out = [];
    foreach ( $items as $ext ) {
        $ext = ltrim( strtolower( (string) $ext ), '. ' );
        $ext = preg_replace( '/[^a-z0-9]+/', '', $ext );
        if ( $ext !== '' ) {
            $out[] = $ext;
        }
    }
    return array_values( array_unique( $out ) );
}

function ddd_dt_ts_exclude_dirs_from_csv( $raw ): array {
    $items = ddd_dt_ts_csv_list( $raw, 50 );
    $out = [];
    foreach ( $items as $dir ) {
        $dir = trim( (string) $dir );
        $dir = preg_replace( '/[^a-zA-Z0-9._-]+/', '', $dir );
        if ( $dir !== '' ) {
            $out[] = $dir;
        }
    }
    return array_values( array_unique( $out ) );
}

function ddd_dt_ts_realpath( $path ): string {
    $real = realpath( (string) $path );
    return is_string( $real ) ? $real : '';
}

function ddd_dt_ts_path_is_within( $path, $base ): bool {
    $p = ddd_dt_ts_realpath( $path );
    $b = ddd_dt_ts_realpath( $base );
    if ( $p === '' || $b === '' ) {
        return false;
    }
    $b = rtrim( $b, '/\\' ) . DIRECTORY_SEPARATOR;
    $p = rtrim( $p, '/\\' ) . DIRECTORY_SEPARATOR;
    return strpos( $p, $b ) === 0;
}

function ddd_dt_ts_relpath_from_content( $path ): string {
    $path = ddd_dt_ts_realpath( $path );
    if ( $path === '' ) {
        return '';
    }
    $base = ddd_dt_ts_realpath( WP_CONTENT_DIR );
    if ( $base && strpos( $path, rtrim( $base, '/\\' ) . DIRECTORY_SEPARATOR ) === 0 ) {
        return ltrim( str_replace( $base, '', $path ), '/\\' );
    }
    return basename( $path );
}
