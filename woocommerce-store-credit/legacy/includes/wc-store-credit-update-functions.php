<?php
/**
 * Functions for updating data, used by the background updater.
 *
 * @since 2.4.0
 * @deprecated 5.0.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Processes a large collection of items in batches.
 *
 * @since 3.0.0
 * @deprecated 5.0.0
 *
 * @param string $option_name the option name to fetch the items to process
 * @param callable|callable-string $callback a callable function to process each item
 * @param array $args optional additional arguments to pass to the callback
 * @param int $items_per_batch optional number of items to process per batch
 * @return bool
 */
function wc_store_credit_update_batch_process( string $option_name, $callback, array $args = [], int $items_per_batch = 50 ) : bool {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );

	$items = (array) get_option( $option_name, [] );

	if ( empty( $items ) ) {
		return false;
	}

	// Process the items in batches.
	$batch = array_slice( $items, 0, $items_per_batch );

	foreach ( $batch as $item ) {
		call_user_func( $callback, $item, $args );
	}

	// Remove the processed items in this batch.
	$items = array_slice( $items, count( $batch ) );

	if ( ! empty( $items ) ) {
		return update_option( $option_name, $items );
	}

	delete_option( $option_name );

	return false;
}

/**
 * Stores the orders that need to synchronize the credit used.
 *
 * @since 2.4.0
 * @deprecated 5.0.0
 *
 * @return void
 */
function wc_store_credit_update_240_orders_to_sync_credit_used() : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}

/**
 * Synchronizes the credit used by the orders.
 *
 * @return bool
 */
function wc_store_credit_update_240_sync_credit_used_by_orders() : bool {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );

	return false;
}

/**
 * Synchronizes the credit used by the order.
 *
 * @since 2.4.0
 * @deprecated 5.0.0
 *
 * @return void
 */
function wc_store_credit_update_240_sync_credit_used_by_order() : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}

/**
 * Sets the payment method to 'Store credit' to the orders paid with a store credit coupon.
 *
 * @since 2.4.0
 * @deprecated 5.0.0
 *
 * @return void
 */
function wc_store_credit_update_240_set_payment_method_to_orders() : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}

/**
 * Clears the remaining credit from trashed store credit coupons.
 *
 * The coupons were trashed without decreasing the credit.
 *
 * @since 2.4.0
 * @deprecated 5.0.0
 *
 * @return void
 */
function wc_store_credit_update_240_clear_exhausted_coupons() : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}

/**
 * Updates DB Version.
 *
 * @since 2.4.0
 * @deprecated 5.0.0
 *
 * @return void
 */
function wc_store_credit_update_240_db_version() : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}

/**
 * Migrates the plugin settings to the new version.
 *
 * @since 3.0.0
 * @deprecated 5.0.0
 *
 * @return void
 */
function wc_store_credit_update_300_migrate_settings() : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}

/**
 * Stores the orders that need to update the version used to calculate the store credit discounts.
 */
function wc_store_credit_update_300_orders_to_update_credit_version() : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}

/**
 * Updates the version used to calculate the store credit discounts in older orders.
 *
 * @since 3.0.0
 * @deprecated 5.0.0
 *
 * @return bool
 */
function wc_store_credit_update_300_update_orders_credit_version() : bool {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );

	return false;
}

/**
 * Updates the version used to calculate the discounts for the specified order.
 *
 * @since 3.0.0
 * @deprecated 5.0.0
 *
 * @return void
 */
function wc_store_credit_update_300_update_order_credit_version() : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}

/**
 * Stores the orders that need to update the discounts applied by store credit coupons.
 *
 * @since 3.0.0
 * @deprecated 5.0.0
 *
 * @return void
 */
function wc_store_credit_update_300_orders_to_update_credit_discounts() : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}

/**
 * Updates the discounts applied by store credit coupons in older orders.
 *
 * @since 3.0.0
 * @deprecated 5.0.0
 *
 * @return bool
 */
function wc_store_credit_update_300_update_orders_credit_discounts() : bool {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );

	return false;
}

/**
 * Updates the discounts applied by store credit coupons for the specified order.
 *
 * @since 3.0.0
 * @deprecated 5.0.0
 *
 * @return void
 */
function wc_store_credit_update_300_update_order_credit_discounts() : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}

/**
 * Stores the coupon that need to be updated.
 *
 * @since 3.0.0
 * @deprecated 5.0.0
 *
 * @return void
 */
function wc_store_credit_update_300_coupons_to_update() : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}

/**
 * Updates the coupons.
 *
 * Adds the global settings 'inc_tax' and 'apply_to_shipping' as metadata.
 *
 * @return bool
 */
function wc_store_credit_update_300_update_coupons() : bool {

	return false;
}

/**
 * Updates the metadata of a store credit coupon.
 *
 * @since 3.0.0
 * @deprecated 5.0.0
 *
 * @param int $coupon_id
 * @param array $metas
 * @return void
 */
function wc_store_credit_update_300_update_coupon( int $coupon_id, array $metas ) : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}

/**
 * Updates DB Version.
 *
 * @since 3.0.0
 * @deprecated 5.0.0
 *
 * @return void
 */
function wc_store_credit_update_300_db_version() : void {

	wc_deprecated_function( __FUNCTION__, '5.0.0' );
}
