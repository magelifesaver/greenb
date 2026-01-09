<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_TS_PHP_Scanner {
    public static function run( $roots, string $mode, callable $match, array $args ): array {
        $start = microtime( true );
        $exclude = (array) ( $args['exclude_dirs'] ?? [] );
        $exts = (array) ( $args['extensions'] ?? [] );
        $files_only = ! empty( $args['files_only'] );
        $max_results = max( 1, min( 2000, absint( $args['max_results'] ?? 200 ) ) );
        $max_file_bytes = max( 1, min( 1024 * 1024 * 20, absint( $args['max_file_kb'] ?? 1024 ) * 1024 ) );
        $max_ms = max( 1000, min( 20000, absint( $args['max_ms'] ?? 8000 ) ) );

        $scanned = 0;
        $matched_files = 0;
        $total_matches = 0;
        $items = [];

        foreach ( (array) $roots as $root ) {
            $root = ddd_dt_ts_realpath( $root );
            if ( $root === '' ) {
                continue;
            }
            if ( is_file( $root ) ) {
                self::scan_file( $root, $mode, $match, $exts, $max_file_bytes, $files_only, $items, $scanned, $matched_files, $total_matches, $max_results, $start, $max_ms );
                if ( self::stop( $total_matches, $max_results, $start, $max_ms ) ) {
                    break;
                }
                continue;
            }
            if ( ! is_dir( $root ) ) {
                continue;
            }

            $dir_it = new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS );
            $filter = new RecursiveCallbackFilterIterator(
                $dir_it,
                function( $current ) use ( $exclude ) {
                    if ( $current->isDir() ) {
                        $base = $current->getBasename();
                        if ( $base && in_array( $base, $exclude, true ) ) {
                            return false;
                        }
                    }
                    return true;
                }
            );
            $it = new RecursiveIteratorIterator( $filter );

            foreach ( $it as $f ) {
                if ( ! $f->isFile() ) {
                    continue;
                }
                $path = $f->getPathname();
                if ( self::skip_file( $path, $exts, $max_file_bytes ) ) {
                    continue;
                }
                self::scan_file( $path, $mode, $match, $exts, $max_file_bytes, $files_only, $items, $scanned, $matched_files, $total_matches, $max_results, $start, $max_ms );
                if ( self::stop( $total_matches, $max_results, $start, $max_ms ) ) {
                    break 2;
                }
            }
        }

        $duration_ms = (int) round( ( microtime( true ) - $start ) * 1000 );
        return [
            'meta' => [
                'scanned_files' => $scanned,
                'matched_files' => $matched_files,
                'matches'       => $total_matches,
                'truncated'     => ( $total_matches >= $max_results ) || ( $duration_ms >= $max_ms ),
            ],
            'items' => array_values( $items ),
        ];
    }

    private static function scan_file( string $path, string $mode, callable $match, array $exts, int $max_file_bytes, bool $files_only, array &$items, int &$scanned, int &$matched_files, int &$total_matches, int $max_results, float $start, int $max_ms ) {
        $scanned++;
        $rel = ddd_dt_ts_relpath_from_content( $path );

        if ( $mode === 'filename' ) {
            if ( $match( $rel ) ) {
                $matched_files++;
                $items[ $rel ] = [ 'file' => $rel, 'matches' => [] ];
            }
            return;
        }

        if ( self::looks_binary( $path ) ) {
            return;
        }

        $fh = new SplFileObject( $path, 'r' );
        $fh->setFlags( SplFileObject::DROP_NEW_LINE );
        $hits = [];
        $line_no = 0;

        foreach ( $fh as $line ) {
            $line_no++;
            if ( $line === null ) {
                continue;
            }
            if ( $match( (string) $line ) ) {
                if ( $files_only ) {
                    $items[ $rel ] = [ 'file' => $rel, 'matches' => [] ];
                    $matched_files++;
                    return;
                }
                $hits[] = [ 'line' => $line_no, 'text' => esc_html( (string) $line ) ];
                $total_matches++;
                if ( self::stop( $total_matches, $max_results, $start, $max_ms ) ) {
                    break;
                }
            }
        }

        if ( ! empty( $hits ) ) {
            $items[ $rel ] = [ 'file' => $rel, 'matches' => $hits ];
            $matched_files++;
        }
    }

    private static function skip_file( string $path, array $exts, int $max_file_bytes ): bool {
        if ( ! is_readable( $path ) || filesize( $path ) > $max_file_bytes ) {
            return true;
        }
        if ( empty( $exts ) ) {
            return false;
        }
        $ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
        return $ext === '' || ! in_array( $ext, $exts, true );
    }

    private static function looks_binary( string $path ): bool {
        $fh = @fopen( $path, 'rb' );
        if ( ! $fh ) {
            return true;
        }
        $chunk = (string) fread( $fh, 512 );
        fclose( $fh );
        return strpos( $chunk, "\0" ) !== false;
    }

    private static function stop( int $total_matches, int $max_results, float $start, int $max_ms ): bool {
        return ( $total_matches >= $max_results ) || ( ( microtime( true ) - $start ) * 1000 ) >= $max_ms;
    }
}
