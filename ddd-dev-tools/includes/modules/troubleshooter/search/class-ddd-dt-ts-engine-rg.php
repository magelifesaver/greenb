<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_TS_Engine_Rg {
    public static function search( $roots, array $args ): array {
        if ( ! ddd_dt_ts_command_exists( 'rg' ) ) return [ 'ok' => false, 'error' => 'ripgrep (rg) not available (or shell_exec disabled).' ];
        if ( ! ddd_dt_ts_is_proc_open_enabled() ) return [ 'ok' => false, 'error' => 'proc_open is not available on this server.' ];

        $start = microtime( true );
        $term = trim( (string) ( $args['term'] ?? '' ) );
        if ( $term === '' ) return [ 'ok' => false, 'error' => 'Please enter a search term.' ];

        $ignore_case = ! empty( $args['ignore_case'] );
        $whole_word = ! empty( $args['whole_word'] );
        $regex = ! empty( $args['regex'] );
        $files_only = ! empty( $args['files_only'] );
        $exts = (array) ( $args['extensions'] ?? [] );
        $exclude = (array) ( $args['exclude_dirs'] ?? [] );
        $max_results = max( 1, min( 2000, absint( $args['max_results'] ?? 200 ) ) );
        $max_ms = max( 1000, min( 20000, absint( $args['max_ms'] ?? 8000 ) ) );
        $max_kb = max( 1, min( 1024 * 1024 * 20, absint( $args['max_file_kb'] ?? 1024 ) * 1024 ) );

        $items = []; $total_matches = 0; $warnings = [];
        foreach ( (array) $roots as $root ) {
            $root = ddd_dt_ts_realpath( $root );
            if ( $root === '' ) continue;
            $cmd = self::build_cmd( $root, $term, $ignore_case, $whole_word, $regex, $files_only, $exts, $exclude, $max_kb );
            $out = self::run_cmd( $cmd, $files_only, $items, $total_matches, $max_results, $start, $max_ms, $warnings );
            if ( ! $out['ok'] ) return $out;
            if ( $out['stop'] ) break;
        }

        $duration_ms = (int) round( ( microtime( true ) - $start ) * 1000 );
        return [
            'ok'   => true,
            'meta' => [
                'engine'        => 'rg',
                'mode'          => 'content',
                'scanned_files' => 0,
                'matched_files' => count( $items ),
                'matches'       => $total_matches,
                'truncated'     => ( $total_matches >= $max_results ) || ( $duration_ms >= $max_ms ),
                'duration_ms'   => $duration_ms,
                'warnings'      => array_values( array_unique( $warnings ) ),
            ],
            'items' => array_values( $items ),
        ];
    }

    private static function build_cmd( string $root, string $term, bool $ignore_case, bool $whole_word, bool $regex, bool $files_only, array $exts, array $exclude, int $max_bytes ): string {
        $parts = [ 'rg', '--color=never', '--no-heading', '--line-number' ];
        if ( $ignore_case ) $parts[] = '-i';
        if ( $whole_word ) $parts[] = '-w';
        if ( $files_only ) $parts[] = '--files-with-matches';
        if ( ! $regex ) $parts[] = '--fixed-strings';
        $parts[] = '--max-filesize';
        $parts[] = escapeshellarg( (int) ceil( $max_bytes / 1024 ) . 'K' );

        foreach ( (array) $exts as $ext ) {
            $ext = preg_replace( '/[^a-z0-9]+/', '', strtolower( (string) $ext ) );
            if ( $ext === '' ) continue;
            $parts[] = '-g'; $parts[] = escapeshellarg( '*.' . $ext );
        }
        foreach ( (array) $exclude as $dir ) {
            $dir = preg_replace( '/[^a-zA-Z0-9._-]+/', '', (string) $dir );
            if ( $dir === '' ) continue;
            $parts[] = '-g'; $parts[] = escapeshellarg( '!' . $dir . '/**' );
        }

        $parts[] = '-e'; $parts[] = escapeshellarg( $term );
        $parts[] = escapeshellarg( $root );
        return implode( ' ', $parts );
    }

    private static function run_cmd( string $cmd, bool $files_only, array &$items, int &$total_matches, int $max_results, float $start, int $max_ms, array &$warnings ): array {
        $desc = [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ];
        $proc = @proc_open( $cmd, $desc, $pipes );
        if ( ! is_resource( $proc ) ) return [ 'ok' => false, 'error' => 'Failed to start rg process.' ];
        stream_set_blocking( $pipes[1], true );

        $stop = false;
        while ( ( $line = fgets( $pipes[1] ) ) !== false ) {
            $line = rtrim( $line, "\r\n" );
            if ( $files_only ) {
                $rel = ddd_dt_ts_relpath_from_content( $line );
                $items[ $rel ] = [ 'file' => $rel, 'matches' => [] ];
            } else {
                $parts = explode( ':', $line, 3 );
                if ( count( $parts ) < 3 ) continue;
                $rel = ddd_dt_ts_relpath_from_content( $parts[0] );
                $ln = absint( $parts[1] );
                $txt = esc_html( (string) $parts[2] );
                if ( ! isset( $items[ $rel ] ) ) $items[ $rel ] = [ 'file' => $rel, 'matches' => [] ];
                $items[ $rel ]['matches'][] = [ 'line' => $ln, 'text' => $txt ];
                $total_matches++;
            }
            if ( $total_matches >= $max_results || ( ( microtime( true ) - $start ) * 1000 ) >= $max_ms ) { $stop = true; break; }
        }

        if ( $stop ) @proc_terminate( $proc );
        fclose( $pipes[1] );
        $err = stream_get_contents( $pipes[2] );
        fclose( $pipes[2] );
        @proc_close( $proc );

        $err = is_string( $err ) ? trim( $err ) : '';
        if ( $err !== '' ) $warnings[] = sanitize_text_field( $err );
        return [ 'ok' => true, 'stop' => $stop ];
    }
}
