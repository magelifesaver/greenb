<?php

/**
 * Returns the legacy main instance of the plugin.
 *
 * @since 3.0.0
 * @deprecated 5.0.0
 *
 * @return WC_Store_Credit
 */
function wc_store_credit() : WC_Store_Credit {

	wc_deprecated_function( __FUNCTION__, '5.0.0', \Kestrel\Store_Credit\Plugin::class . '::instance()' );

	return WC_Store_Credit::instance();
}
