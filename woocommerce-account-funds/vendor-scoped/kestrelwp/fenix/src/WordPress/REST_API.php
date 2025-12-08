<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress;

defined('ABSPATH') or exit;
/**
 * WordPress REST API helper.
 *
 * @since 1.1.4
 */
final class REST_API
{
    /**
     * Determines if the current request is a REST API request.
     *
     * @link https://core.trac.wordpress.org/ticket/42061 should swich to an internal hepler when available
     *
     * @see \Kestrel\Account_Funds\Scoped\wcs_is_rest_api_request()
     * @see WooCommerce::is_rest_api_request()
     *
     * @since 1.1.4
     *
     * @return bool
     */
    public static function is_rest_request(): bool
    {
        if (defined('REST_REQUEST') && \REST_REQUEST) {
            return \true;
        }
        // @phpstan-ignore-next-line
        if (function_exists('WC') && is_callable([WC(), 'is_rest_api_request'])) {
            return WC()->is_rest_api_request();
        }
        if (!function_exists('rest_get_url_prefix')) {
            return \false;
        }
        return !empty($_SERVER['REQUEST_URI']) && \false !== strpos($_SERVER['REQUEST_URI'], trailingslashit(rest_get_url_prefix()));
        // phpcs:ignore
    }
}
