<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/scheduler/admin/tabs/tab-sameday.php
 * Purpose: Same-Day Delivery settings tab (custom-table, independent key)
 * Version: 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helper: load Same-Day options from custom table.
 */
function adbsa_get_sameday_opts() {
	global $wpdb;
	$table = $wpdb->prefix . 'aaa_oc_options';
	$val   = $wpdb->get_var( $wpdb->prepare(
		"SELECT option_value FROM {$table} WHERE option_key = %s LIMIT 1",
		'adbsa_options_sameday'
	) );
	$out = maybe_unserialize( $val );
	return is_array( $out ) ? $out : [];
}
$opt = adbsa_get_sameday_opts();

// ========================
// Section: General (Enable + Zone + Method)
// ========================
add_settings_section(
	'adbsa_sameday_general',
	'General Settings',
	'__return_false',
	'adbsa_settings_page_sameday'
);

// Enable toggle
add_settings_field(
	'adbsa_sameday_enabled',
	'Enable Same-Day',
	function() use ( $opt ) {
		$val = ! empty( $opt['enabled'] );
		?>
		<input type="checkbox" name="adbsa_options_sameday[enabled]" value="1" <?php checked( $val ); ?> />
		<?php
	},
	'adbsa_settings_page_sameday',
	'adbsa_sameday_general'
);

// Zone selector
add_settings_field(
	'adbsa_sameday_zone',
	'Shipping Zone',
	function() use ( $opt ) {
		$zone_id = $opt['zone_id'] ?? '';
		$zones   = WC_Shipping_Zones::get_zones();
		?>
		<select name="adbsa_options_sameday[zone_id]">
			<option value="">Select a Zone</option>
			<?php foreach ( $zones as $zid => $zone ) : ?>
				<option value="<?php echo esc_attr( $zid ); ?>" <?php selected( $zone_id, $zid ); ?>>
					<?php echo esc_html( $zone['zone_name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	},
	'adbsa_settings_page_sameday',
	'adbsa_sameday_general'
);

// Method selector
add_settings_field(
	'adbsa_sameday_method',
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
		<select name="adbsa_options_sameday[method_instance_id]">
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
	'adbsa_settings_page_sameday',
	'adbsa_sameday_general'
);

// ========================
// Section: Business Hours
// ========================
add_settings_section(
	'adbsa_sameday_hours',
	'Business Hours',
	'__return_false',
	'adbsa_settings_page_sameday'
);

add_settings_field(
	'adbsa_sameday_open',
	'Open Time',
	function() use ( $opt ) {
		$val = $opt['open_time'] ?? '11:00';
		?><input type="time" name="adbsa_options_sameday[open_time]" value="<?php echo esc_attr( $val ); ?>" /><?php
	},
	'adbsa_settings_page_sameday',
	'adbsa_sameday_hours'
);

add_settings_field(
	'adbsa_sameday_cutoff',
	'Cutoff Time',
	function() use ( $opt ) {
		$val = $opt['cutoff_time'] ?? '21:45';
		?>
		<input type="time" name="adbsa_options_sameday[cutoff_time]" value="<?php echo esc_attr( $val ); ?>" />
		<p class="description">No Same-Day slots will be available after this time.</p>
		<?php
	},
	'adbsa_settings_page_sameday',
	'adbsa_sameday_hours'
);

add_settings_field(
	'adbsa_sameday_lastslot_start',
	'Last Slot Start Time',
	function() use ( $opt ) {
		$val = $opt['lastslot_start'] ?? '';
		?>
		<input type="time" name="adbsa_options_sameday[lastslot_start]" value="<?php echo esc_attr( $val ); ?>" />
		<p class="description">Defines the start time of the final slot (ends at cutoff).</p>
		<?php
	},
	'adbsa_settings_page_sameday',
	'adbsa_sameday_hours'
);

// ========================
// Section: Start Time Calculation
// ========================
add_settings_section(
	'adbsa_sameday_start',
	'Start Time Calculation',
	'__return_false',
	'adbsa_settings_page_sameday'
);

add_settings_field(
	'adbsa_sameday_processing',
	'Processing Time',
	function() use ( $opt ) {
		$enabled = ! empty( $opt['processing_enabled'] );
		$minutes = $opt['processing_minutes'] ?? 0;
		?>
		<label><input type="checkbox" name="adbsa_options_sameday[processing_enabled]" value="1" <?php checked( $enabled ); ?> /> Enable</label>
		<input type="number" name="adbsa_options_sameday[processing_minutes]" value="<?php echo esc_attr( $minutes ); ?>" min="0" step="1" /> minutes
		<?php
	},
	'adbsa_settings_page_sameday',
	'adbsa_sameday_start'
);

add_settings_field(
	'adbsa_sameday_travel',
	'Travel Time',
	function() use ( $opt ) {
		$enabled = ! empty( $opt['travel_enabled'] );
		$mode    = $opt['travel_mode'] ?? 'static';
		$static  = $opt['travel_static'] ?? 0;
		?>
		<label><input type="checkbox" name="adbsa_options_sameday[travel_enabled]" value="1" <?php checked( $enabled ); ?> /> Enable</label><br/>
		<label><input type="radio" name="adbsa_options_sameday[travel_mode]" value="static" <?php checked( $mode, 'static' ); ?> /> Static:</label>
		<input type="number" name="adbsa_options_sameday[travel_static]" value="<?php echo esc_attr( $static ); ?>" min="0" step="1" /> minutes<br/>
		<label><input type="radio" name="adbsa_options_sameday[travel_mode]" value="dynamic" <?php checked( $mode, 'dynamic' ); ?> /> Use dynamic travel time (Geo plugin)</label>
		<?php
	},
	'adbsa_settings_page_sameday',
	'adbsa_sameday_start'
);

add_settings_field(
	'adbsa_sameday_buffer',
	'Buffer Time',
	function() use ( $opt ) {
		$enabled = ! empty( $opt['buffer_enabled'] );
		$minutes = $opt['buffer_minutes'] ?? 0;
		?>
		<label><input type="checkbox" name="adbsa_options_sameday[buffer_enabled]" value="1" <?php checked( $enabled ); ?> /> Enable</label>
		<input type="number" name="adbsa_options_sameday[buffer_minutes]" value="<?php echo esc_attr( $minutes ); ?>" min="0" step="1" /> minutes
		<?php
	},
	'adbsa_settings_page_sameday',
	'adbsa_sameday_start'
);

add_settings_field(
	'adbsa_sameday_round',
	'Round Start To Interval',
	function() use ( $opt ) {
		$val = $opt['round_interval'] ?? 15;
		?>
		<select name="adbsa_options_sameday[round_interval]">
			<?php foreach ( [5,10,15,30,60] as $m ) : ?>
				<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $val, $m ); ?>><?php echo $m; ?> minutes</option>
			<?php endforeach; ?>
		</select>
		<?php
	},
	'adbsa_settings_page_sameday',
	'adbsa_sameday_start'
);

// ========================
// Section: Slot Duration & Interval
// ========================
add_settings_section(
	'adbsa_sameday_duration',
	'Slot Duration / Interval',
	'__return_false',
	'adbsa_settings_page_sameday'
);

add_settings_field(
	'adbsa_sameday_length',
	'Slot Length',
	function() use ( $opt ) {
		$val = $opt['slot_length'] ?? 60;
		?>
		<input type="number" name="adbsa_options_sameday[slot_length]" value="<?php echo esc_attr( $val ); ?>" min="0" step="1" /> minutes
		<?php
	},
	'adbsa_settings_page_sameday',
	'adbsa_sameday_duration'
);

add_settings_field(
	'adbsa_sameday_step',
	'Slot Step / Overlap',
	function() use ( $opt ) {
		$overlap_mode = ! empty( $opt['overlap_mode'] );
		$step     = $opt['slot_step'] ?? 0;
		$overlap  = $opt['overlap_minutes'] ?? 0;
		?>
		<label><input type="checkbox" name="adbsa_options_sameday[overlap_mode]" value="1" <?php checked( $overlap_mode ); ?> /> Enable Overlap Mode</label><br/><br/>
		<?php if ( $overlap_mode ) : ?>
			<input type="number" name="adbsa_options_sameday[overlap_minutes]" value="<?php echo esc_attr( $overlap ); ?>" min="0" step="1" /> minutes
			<p class="description">Example: Length 60, Overlap 30 → new slot every 30 minutes.</p>
		<?php else : ?>
			<input type="number" name="adbsa_options_sameday[slot_step]" value="<?php echo esc_attr( $step ); ?>" min="0" step="1" /> minutes
			<p class="description">Example: 60 = back-to-back, 30 = half-hourly, 0 = disabled rolling overlap.</p>
		<?php endif; ?>
		<?php
	},
	'adbsa_settings_page_sameday',
	'adbsa_sameday_duration'
);
