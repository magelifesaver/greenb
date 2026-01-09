<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

require_once DDD_DT_DIR . 'includes/modules/troubleshooter/search/class-ddd-dt-ts-php-matcher.php';
require_once DDD_DT_DIR . 'includes/modules/troubleshooter/search/class-ddd-dt-ts-php-scanner.php';

class DDD_DT_TS_Engine_PHP {
    public static function search( $roots, array $args ): array {
        $start = microtime( true );

        $term = (string) ( $args['term'] ?? '' );
        $mode = ( $args['mode'] ?? '' ) === 'filename' ? 'filename' : 'content';
        $ignore_case = ! empty( $args['ignore_case'] );
        $whole_word = ! empty( $args['whole_word'] );
        $regex = ! empty( $args['regex'] );

        $matcher = DDD_DT_TS_PHP_Matcher::build( $term, (bool) $ignore_case, (bool) $whole_word, (bool) $regex );
        if ( ! $matcher['ok'] ) {
            return [ 'ok' => false, 'error' => $matcher['error'] ];
        }

        $scan = DDD_DT_TS_PHP_Scanner::run( (array) $roots, $mode, $matcher['match'], $args );
        $duration_ms = (int) round( ( microtime( true ) - $start ) * 1000 );

        return [
            'ok'   => true,
            'meta' => array_merge(
                $scan['meta'],
                [
                    'engine'      => 'php',
                    'mode'        => $mode,
                    'duration_ms' => $duration_ms,
                ]
            ),
            'items' => $scan['items'],
        ];
    }
}
