<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_TS_PHP_Matcher {
    public static function build( $term, bool $ignore_case, bool $whole_word, bool $regex ): array {
        $term = trim( (string) $term );
        if ( $term === '' ) {
            return [ 'ok' => false, 'error' => 'Please enter a search term.' ];
        }

        $use_regex = $regex || $whole_word;
        if ( $use_regex ) {
            $pattern = self::build_pattern( $term, $ignore_case, $whole_word, $regex );
            $ok = @preg_match( $pattern, 'test' );
            if ( $ok === false ) {
                return [ 'ok' => false, 'error' => 'Invalid regex pattern.' ];
            }
            return [
                'ok' => true,
                'match' => function( $text ) use ( $pattern ) {
                    return @preg_match( $pattern, (string) $text ) === 1;
                },
            ];
        }

        return [
            'ok' => true,
            'match' => function( $text ) use ( $term, $ignore_case ) {
                return $ignore_case ? ( stripos( (string) $text, $term ) !== false ) : ( strpos( (string) $text, $term ) !== false );
            },
        ];
    }

    private static function build_pattern( string $term, bool $ignore_case, bool $whole_word, bool $regex ): string {
        $delim = self::pick_delim( $term );
        if ( $whole_word ) {
            $body = '\\b' . preg_quote( $term, $delim ) . '\\b';
            return $delim . $body . $delim . ( $ignore_case ? 'i' : '' );
        }
        $body = $regex ? $term : preg_quote( $term, $delim );
        return $delim . $body . $delim . ( $ignore_case ? 'i' : '' );
    }

    private static function pick_delim( string $term ): string {
        foreach ( [ '~', '#', '@', '%' ] as $d ) {
            if ( strpos( $term, $d ) === false ) {
                return $d;
            }
        }
        return '~';
    }
}
