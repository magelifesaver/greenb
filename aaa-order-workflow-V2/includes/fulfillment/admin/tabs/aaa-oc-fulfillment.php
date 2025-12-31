<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/fulfillment/admin/tabs/aaa-oc-fulfillment.php
 * Purpose: Fulfillment & Packing settings tab (WFCP). Controls when picking/packing UI shows and how it behaves.
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure option helpers exist (FIX: correct path — 'core', not 'core1')
if ( ! function_exists( 'aaa_oc_get_option' ) ) {
	require_once dirname( __FILE__, 4 ) . '/core/options/class-aaa-oc-options.php';
    AAA_OC_Options::init();
}

/** Scope + Keys */
$scope = 'fulfillment';

/** Defaults */
$defaults = [
    'pick_enable_status'       => 'processing',
    'allow_partial_picking'    => 1,
    'pick_complete_status'     => 'lkd-packed-ready',
    'pick_time_limit_minutes'  => 0,
    'pick_time_flash_color'    => '#ff3b30',

    'pack_enable'              => 0,
    'pack_show_status'         => 'lkd-packed-ready',
    'pack_complete_status'     => 'completed',
    'pack_require_picked'      => 1,
    'pack_allow_partial'       => 0,
    'pack_time_limit_minutes'  => 0,
    'pack_time_flash_color'    => '#ff3b30',
    'additional_sku_meta_keys' => '',
];

/** Load current values */
$vals = [];
foreach ( $defaults as $k => $def ) {
    $vals[ $k ] = aaa_oc_get_option( $k, $scope, $def );
}

/** Handle Save */
if ( isset( $_POST['save_fulfillment_tab'] ) && check_admin_referer( 'aaa_oc_fulfillment_save' ) ) {

    // Build a whitelist of valid WC status slugs (no 'wc-' prefix)
    $statuses    = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : [];
    $valid_slugs = array_map( static fn($wc) => ltrim($wc, 'wc-'), array_keys( $statuses ) );

    $sanitize_status = static function( $field, $fallback ) use ( $valid_slugs ) {
        $in = isset( $_POST[ $field ] ) ? sanitize_title( wp_unslash( $_POST[ $field ] ) ) : $fallback;
        return in_array( $in, $valid_slugs, true ) ? $in : $fallback;
    };
    $set_bool = static fn( $field, $fallback = 0 ) => ( ! empty( $_POST[ $field ] ) ? 1 : (int) $fallback );
    $set_int  = static fn( $field, $fallback = 0 ) => max( 0, (int) ($_POST[ $field ] ?? $fallback ) );
    $set_color= static function( $field, $fallback = '#ff3b30' ) {
        $raw = trim( (string) ( $_POST[ $field ] ?? $fallback ) );
        return preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $raw ) ? $raw : $fallback;
    };

    // Picking
    $vals['pick_enable_status']      = $sanitize_status( 'pick_enable_status',      $vals['pick_enable_status'] );
    $vals['allow_partial_picking']   = $set_bool(       'allow_partial_picking',    $vals['allow_partial_picking'] );
    $vals['pick_complete_status']    = $sanitize_status( 'pick_complete_status',     $vals['pick_complete_status'] );
    $vals['pick_time_limit_minutes'] = $set_int(        'pick_time_limit_minutes',  $vals['pick_time_limit_minutes'] );
    $vals['pick_time_flash_color']   = $set_color(      'pick_time_flash_color',    $vals['pick_time_flash_color'] );

    // Packing
    $vals['pack_enable']             = $set_bool( 'pack_enable', $vals['pack_enable'] );
    $vals['pack_show_status']        = $sanitize_status( 'pack_show_status',    $vals['pack_show_status'] );
    $vals['pack_complete_status']    = $sanitize_status( 'pack_complete_status',$vals['pack_complete_status'] );
    $vals['pack_require_picked']     = $set_bool( 'pack_require_picked',  $vals['pack_require_picked'] );
    $vals['pack_allow_partial']      = $set_bool( 'pack_allow_partial',   $vals['pack_allow_partial'] );
    $vals['pack_time_limit_minutes'] = $set_int(  'pack_time_limit_minutes', $vals['pack_time_limit_minutes'] );
    $vals['pack_time_flash_color']   = $set_color('pack_time_flash_color',   $vals['pack_time_flash_color'] );

	// Extra SKU meta keys (comma-separated)
	if ( isset( $_POST['additional_sku_meta_keys'] ) ) {
	    $raw = sanitize_text_field( wp_unslash( $_POST['additional_sku_meta_keys'] ) );
	    // normalize commas + spaces
	    $vals['additional_sku_meta_keys'] = trim( preg_replace( '/\s*,\s*/', ',', $raw ), ',' );
	}

    // Persist everything under the 'fulfillment' scope
    foreach ( $vals as $k => $v ) {
        aaa_oc_set_option( $k, $v, $scope );
    }

    echo '<div class="updated"><p>' . esc_html__( 'Fulfillment settings saved.', 'aaa-oc' ) . '</p></div>';
}

/** Build status select options (no 'wc-') */
$choices = [];
if ( function_exists( 'wc_get_order_statuses' ) ) {
    foreach ( wc_get_order_statuses() as $wc_key => $label ) {
        $choices[ ltrim( $wc_key, 'wc-' ) ] = $label;
    }
}
?>
<div class="wrap">
    <h2><?php esc_html_e( 'Fulfillment & Packing Settings', 'aaa-oc' ); ?></h2>

    <form method="post">
        <?php wp_nonce_field( 'aaa_oc_fulfillment_save' ); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Enable picking for status', 'aaa-oc' ); ?></th>
                <td>
                    <select name="pick_enable_status">
                        <?php foreach ( $choices as $slug => $label ) : ?>
                            <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $vals['pick_enable_status'], $slug ); ?>>
                                <?php echo esc_html( "{$label} ({$slug})" ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'When an order is in this status, show the Fulfillment picking table on the Board.', 'aaa-oc' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Allow partial picking', 'aaa-oc' ); ?></th>
                <td>
                    <label><input type="checkbox" name="allow_partial_picking" value="1" <?php checked( $vals['allow_partial_picking'], 1 ); ?> />
                        <?php esc_html_e( 'Permit saving progress and continuing later.', 'aaa-oc' ); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Status after fully picked', 'aaa-oc' ); ?></th>
                <td>
                    <select name="pick_complete_status">
                        <?php foreach ( $choices as $slug => $label ) : ?>
                            <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $vals['pick_complete_status'], $slug ); ?>>
                                <?php echo esc_html( "{$label} ({$slug})" ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Picking time limit (minutes)', 'aaa-oc' ); ?></th>
                <td>
                    <input type="number" min="0" step="1" name="pick_time_limit_minutes" value="<?php echo esc_attr( (int) $vals['pick_time_limit_minutes'] ); ?>" />
                    <input type="text" name="pick_time_flash_color" value="<?php echo esc_attr( (string) $vals['pick_time_flash_color'] ); ?>" placeholder="#ff3b30" />
                    <p class="description"><?php esc_html_e( 'Set to 0 to disable. Color is used for flashing when the limit is reached.', 'aaa-oc' ); ?></p>
                </td>
            </tr>

            <tr><th colspan="2"><hr></th></tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Enable Packing', 'aaa-oc' ); ?></th>
                <td>
                    <label><input type="checkbox" name="pack_enable" value="1" <?php checked( $vals['pack_enable'], 1 ); ?> />
                        <?php esc_html_e( 'Show packing columns / behavior.', 'aaa-oc' ); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Show packing table for status', 'aaa-oc' ); ?></th>
                <td>
                    <select name="pack_show_status">
                        <?php foreach ( $choices as $slug => $label ) : ?>
                            <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $vals['pack_show_status'], $slug ); ?>>
                                <?php echo esc_html( "{$label} ({$slug})" ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Status after fully packed', 'aaa-oc' ); ?></th>
                <td>
                    <select name="pack_complete_status">
                        <?php foreach ( $choices as $slug => $label ) : ?>
                            <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $vals['pack_complete_status'], $slug ); ?>>
                                <?php echo esc_html( "{$label} ({$slug})" ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Prevent packing without picking', 'aaa-oc' ); ?></th>
                <td>
                    <label><input type="checkbox" name="pack_require_picked" value="1" <?php checked( $vals['pack_require_picked'], 1 ); ?> />
                        <?php esc_html_e( 'Require an order to be fully picked before packing.', 'aaa-oc' ); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Allow partial packing', 'aaa-oc' ); ?></th>
                <td>
                    <label><input type="checkbox" name="pack_allow_partial" value="1" <?php checked( $vals['pack_allow_partial'], 1 ); ?> />
                        <?php esc_html_e( 'Permit saving partial packing and finishing later.', 'aaa-oc' ); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Packing time limit (minutes)', 'aaa-oc' ); ?></th>
                <td>
                    <input type="number" min="0" step="1" name="pack_time_limit_minutes" value="<?php echo esc_attr( (int) $vals['pack_time_limit_minutes'] ); ?>" />
                    <input type="text" name="pack_time_flash_color" value="<?php echo esc_attr( (string) $vals['pack_time_flash_color'] ); ?>" placeholder="#ff3b30" />
                </td>
            </tr>
	    <tr>
		    <th scope="row"><?php esc_html_e( 'Additional SKU Meta Keys', 'aaa-oc' ); ?></th>
		    <td>
		        <input type="text" name="additional_sku_meta_keys"
		               value="<?php echo esc_attr( (string) $vals['additional_sku_meta_keys'] ); ?>"
		               placeholder="e.g. alt_sku, warehouse_sku, vendor_sku" style="width:100%;">
		        <p class="description">
		            <?php esc_html_e( 'Optional. Comma-separated list of product meta keys to check for alternate SKUs during picking/packing (in addition to lkd_wm_new_sku).', 'aaa-oc' ); ?>
		        </p>
		    </td>
		</tr>

        </table>

        <p class="submit">
            <button type="submit" name="save_fulfillment_tab" class="button button-primary">
                <?php esc_html_e( 'Save Settings', 'aaa-oc' ); ?>
            </button>
        </p>
    </form>
</div>
