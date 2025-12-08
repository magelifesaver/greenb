<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/scheduler/admin/tabs/tab-scheduled.php
 * Purpose: Scheduled Delivery settings tab (custom-table independent key)
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Helper: load Scheduled options from custom table */
function adbsa_get_scheduled_opts() {
	global $wpdb;
	$table = $wpdb->prefix . 'aaa_oc_options';
	$val   = $wpdb->get_var( $wpdb->prepare(
		"SELECT option_value FROM {$table} WHERE option_key = %s LIMIT 1",
		'adbsa_options_scheduled'
	) );
	$out = maybe_unserialize( $val );
	return is_array( $out ) ? $out : [];
}
$opt = adbsa_get_scheduled_opts();

/* ========================
 * Section: General (Enable + Zone + Method)
 * ======================== */
add_settings_section(
	'adbsa_scheduled_general',
	'General Settings',
	'__return_false',
	'adbsa_settings_page_scheduled'
);

// Enable toggle
add_settings_field(
	'adbsa_scheduled_enabled',
	'Enable Scheduled Delivery',
	function() use ( $opt ) {
		$val = ! empty( $opt['enabled'] );
		?>
		<input type="checkbox" name="adbsa_options_scheduled[enabled]" value="1" <?php checked( $val ); ?> />
		<?php
	},
	'adbsa_settings_page_scheduled',
	'adbsa_scheduled_general'
);

// Zone selector
add_settings_field(
	'adbsa_scheduled_zone',
	'Shipping Zone',
	function() use ( $opt ) {
		$zone_id = $opt['zone_id'] ?? '';
		$zones   = WC_Shipping_Zones::get_zones();
		?>
		<select name="adbsa_options_scheduled[zone_id]">
			<option value="">Select a Zone</option>
			<?php foreach ( $zones as $zid => $zone ) : ?>
				<option value="<?php echo esc_attr( $zid ); ?>" <?php selected( $zone_id, $zid ); ?>>
					<?php echo esc_html( $zone['zone_name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	},
	'adbsa_settings_page_scheduled',
	'adbsa_scheduled_general'
);

// Method selector
add_settings_field(
	'adbsa_scheduled_method',
	'Shipping Method',
	function() use ( $opt ) {
		$zone_id   = $opt['zone_id'] ?? '';
		$method_id = $opt['method_instance_id'] ?? '';
		$methods   = [];
		if ( $zone_id ) {
			$zone    = new WC_Shipping_Zone( $zone_id );
			$methods = $zone->get_shipping_methods( true );
		}
		?>
		<select name="adbsa_options_scheduled[method_instance_id]">
			<option value="">Select a Shipping Method</option>
			<?php foreach ( $methods as $m ) : ?>
				<option value="<?php echo esc_attr( $m->instance_id ); ?>" <?php selected( $method_id, $m->instance_id ); ?>>
					<?php echo esc_html( $m->get_method_title() . " ({$m->id} #{$m->instance_id})" ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">Save after selecting a Zone to refresh available methods.</p>
		<?php
	},
	'adbsa_settings_page_scheduled',
	'adbsa_scheduled_general'
);

/* ========================
 * Section: Date Window
 * ======================== */
add_settings_section(
	'adbsa_scheduled_dates',
	'Date Window',
	'__return_false',
	'adbsa_settings_page_scheduled'
);

// Start offset
add_settings_field(
	'adbsa_scheduled_start_offset',
	'Start Offset (days from today)',
	function() use ( $opt ) {
		$value = isset( $opt['start_offset_days'] ) ? (int) $opt['start_offset_days'] : 1;
		?>
		<input type="number" name="adbsa_options_scheduled[start_offset_days]" value="<?php echo esc_attr( $value ); ?>" min="0" step="1" />
		<p class="description">0 = today, 1 = tomorrow, etc.</p>
		<?php
	},
	'adbsa_settings_page_scheduled',
	'adbsa_scheduled_dates'
);

// Total selectable days
add_settings_field(
	'adbsa_scheduled_total_days',
	'Total Selectable Days',
	function() use ( $opt ) {
		$value = isset( $opt['total_days'] ) ? (int) $opt['total_days'] : 14;
		?>
		<input type="number" name="adbsa_options_scheduled[total_days]" value="<?php echo esc_attr( $value ); ?>" min="1" step="1" />
		<p class="description">How many future days customers can choose from.</p>
		<?php
	},
	'adbsa_settings_page_scheduled',
	'adbsa_scheduled_dates'
);

/* ========================
 * Section: Static Time Slots
 * ======================== */
add_settings_section(
	'adbsa_scheduled_times',
	'Static Time Slots',
	'__return_false',
	'adbsa_settings_page_scheduled'
);

add_settings_field(
	'adbsa_scheduled_start_time',
	'Start Time',
	function() use ( $opt ) {
		$val = $opt['start_time'] ?? '11:00';
		?>
		<input type="time" name="adbsa_options_scheduled[start_time]" value="<?php echo esc_attr( $val ); ?>" />
		<?php
	},
	'adbsa_settings_page_scheduled',
	'adbsa_scheduled_times'
);

add_settings_field(
	'adbsa_scheduled_end_time',
	'End Time',
	function() use ( $opt ) {
		$val = $opt['end_time'] ?? '21:45';
		?>
		<input type="time" name="adbsa_options_scheduled[end_time]" value="<?php echo esc_attr( $val ); ?>" />
		<?php
	},
	'adbsa_settings_page_scheduled',
	'adbsa_scheduled_times'
);

add_settings_field(
	'adbsa_scheduled_slot_window',
	'Slot Window (minutes)',
	function() use ( $opt ) {
		$val = isset( $opt['slot_window_minutes'] ) ? (int) $opt['slot_window_minutes'] : 60;
		?>
		<input type="number" name="adbsa_options_scheduled[slot_window_minutes]" value="<?php echo esc_attr( $val ); ?>" min="1" step="1" />
		<p class="description">Length of each slot window (e.g. 60).</p>
		<?php
	},
	'adbsa_settings_page_scheduled',
	'adbsa_scheduled_times'
);

add_settings_field(
	'adbsa_scheduled_slot_step',
	'Slot Step (minutes)',
	function() use ( $opt ) {
		$val = isset( $opt['slot_step_minutes'] ) ? (int) $opt['slot_step_minutes'] : 60;
		?>
		<input type="number" name="adbsa_options_scheduled[slot_step_minutes]" value="<?php echo esc_attr( $val ); ?>" min="0" step="1" />
		<p class="description">Spacing between slot starts. 0 = back-to-back.</p>
		<?php
	},
	'adbsa_settings_page_scheduled',
	'adbsa_scheduled_times'
);

/* ========================
 * Section: Capacity
 * ======================== */
add_settings_section(
	'adbsa_scheduled_capacity',
	'Capacity per Slot',
	'__return_false',
	'adbsa_settings_page_scheduled'
);

add_settings_field(
	'adbsa_scheduled_max_orders',
	'Max Orders per Slot',
	function() use ( $opt ) {
		$val = isset( $opt['max_orders_per_slot'] ) ? (int) $opt['max_orders_per_slot'] : 0;
		?>
		<input type="number" name="adbsa_options_scheduled[max_orders_per_slot]" value="<?php echo esc_attr( $val ); ?>" min="0" step="1" />
		<p class="description">0 = unlimited.</p>
		<?php
	},
	'adbsa_settings_page_scheduled',
	'adbsa_scheduled_capacity'
);
