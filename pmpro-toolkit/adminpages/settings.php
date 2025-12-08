<?php

/*
 * Display Settings Sections
 */
function pmprodev_email_settings() {
?>
<style>
	h2, table.form-table {border-bottom: 1px solid #ddd; margin-bottom: 2em;}
</style>
<?php
}

function pmprodev_gateway_settings() {
	?>
	<p><?php echo esc_html_e( 'Enable debugging for PayPal IPNs, Authorize.net Silent Posts, Stripe Webhook, etc. Enter the email address you would like the logs to be emailed to, or leave blank to disable.', 'pmpro-toolkit' ); ?></p>
	<?php
}

function pmprodev_cron_settings() {
	?>
	<p><?php echo esc_html_e( 'Disable scheduled scripts that run daily via WP CRON.', 'pmpro-toolkit' ); ?></p>
	<?php
}

function pmprodev_view_as_settings() {
	global $wpdb;
	// get example level info
	$level = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->pmpro_membership_levels . ' LIMIT 1' );
	$level_name = $level->name;
	$level_id = $level->id;
	$example_link = '<a href="' . add_query_arg( 'pmprodev_view_as', $level_id, home_url() ) . '">' . add_query_arg( 'pmprodev_view_as', $level_id, home_url() ) . '</a>';
	?>
	<p>
		<?php
		esc_html_e( 'Enabling "View as..." will allow admins to view any page as if they had any membership level(s) for a brief period of time.', 'pmpro-toolkit' );
		echo '<br>';
		echo sprintf( esc_html__( 'To use it, add the query string parameter %s to your URL, passing a series of level IDs separated by hyphens.', 'pmpro-toolkit' ), '<code>pmprodev_view_as</code>' );
?>
	</p>
	<p>
		<?php echo sprintf( __( 'For example, view your homepage as %s with the link %s', 'pmpro-toolkit' ), $level_name, $example_link ); ?>
	</p>
	<p>
		<?php esc_html_e( 'Use "r" to reset the "View as" filter, and any nonexistent level ID (for example, "n" will never be a level ID) to emulate having no membership level.', 'pmpro-toolkit' ); ?>
	</p>
<?php
}

/*
 * Display Settings Fields
 */

// redirect emails
function pmprodev_settings_redirect_email() {
	global $pmprodev_options;
	?>
	<input type="email"  name="pmprodev_options[redirect_email]" value="<?php echo esc_attr( $pmprodev_options['redirect_email'] ); ?>">
	<p class="description"><?php echo esc_html_e( 'Redirect all Paid Memberships Pro emails to a specific address.', 'pmpro-toolkit' ); ?></p>
<?php
}

// cron debugging
function pmprodev_settings_cron_expire_memberships() {
	global $pmprodev_options;

	if ( ! isset( $pmprodev_options['expire_memberships'] ) ) {
		$pmprodev_options['expire_memberships'] = 0;
	}

	?>
	<input id="expire_memberships" type="checkbox"  name="pmprodev_options[expire_memberships]" value="1" <?php checked( $pmprodev_options['expire_memberships'], 1, true ); ?>>
	<label for="expire_memberships"><?php echo esc_html_e( 'Check to disable the script that checks for expired memberships.', 'pmpro-toolkit' ); ?></label>
	<?php
}
function pmprodev_settings_cron_expiration_warnings() {
	global $pmprodev_options;

	if ( ! isset( $pmprodev_options['expiration_warnings'] ) ) {
		$pmprodev_options['expiration_warnings'] = 0;
	}

	?>
	<input id="expiration_warnings" type="checkbox"  name="pmprodev_options[expiration_warnings]" value="1" <?php checked( $pmprodev_options['expiration_warnings'], 1, true ); ?>>
	<label for="expiration_warnings"><?php echo esc_html_e( 'Check to disable the script that sends expiration warnings.', 'pmpro-toolkit' ); ?></label>
	<?php
}
function pmprodev_settings_cron_credit_card_expiring() {
	global $pmprodev_options;

	if ( ! isset( $pmprodev_options['credit_card_expiring'] ) ) {
		$pmprodev_options['credit_card_expiring'] = 0;
	}

	?>
	<input id="credit_card_expiring" type="checkbox"  name="pmprodev_options[credit_card_expiring]" value="1" <?php checked( $pmprodev_options['credit_card_expiring'], 1, true ); ?>>
	<label for="credit_card_expiring"><?php echo esc_html_e( 'Check to disable the script that checks for expired credit cards.', 'pmpro-toolkit' ); ?></label>
	<?php
}

// gateway debugging
function pmprodev_settings_ipn_debug() {
	global $pmprodev_options;
	?>
	<input type="email"  name="pmprodev_options[ipn_debug]" value="<?php echo esc_attr( $pmprodev_options['ipn_debug'] ); ?>">
	<?php
}

function pmprodev_settings_checkout_debug_email() {
	global $pmprodev_options;	
	?>
	<select name="pmprodev_options[checkout_debug_when]">
		<option value="" <?php selected( $pmprodev_options['checkout_debug_when'], '' ); ?>><?php esc_html_e( 'Never (Off)', 'pmpro-toolkit' ); ?></option>
		<option value="on_checkout" <?php selected( $pmprodev_options['checkout_debug_when'], 'on_checkout' ); ?>><?php esc_html_e( 'Yes, Every Page Load', 'pmpro-toolkit' ); ?></option>
		<option value="on_submit" <?php selected( $pmprodev_options['checkout_debug_when'], 'on_submit' ); ?>><?php esc_html_e( 'Yes, Submitted Forms Only', 'pmpro-toolkit' ); ?></option>
		<option value="on_error" <?php selected( $pmprodev_options['checkout_debug_when'], 'on_error' ); ?>><?php esc_html_e( 'Yes, Errors Only', 'pmpro-toolkit' ); ?></option>
	</select>
	to email: <input type="email"  name="pmprodev_options[checkout_debug_email]" value="<?php echo esc_attr( $pmprodev_options['checkout_debug_email'] ); ?>">
	<p class="description">
		<?php esc_html_e( 'Send an email every time the Checkout page is hit.', 'pmpro-toolkit' ); ?>
		<br>
		<?php esc_html_e( 'This email will contain data about the request, user, membership level, order, and other information.', 'pmpro-toolkit' ); ?></p>
<?php
}

function pmprodev_settings_view_as_enabled() {
	global $pmprodev_options;

	if ( ! isset( $pmprodev_options['view_as_enabled'] ) ) {
		$pmprodev_options['view_as_enabled'] = 0;
	}

	?>
	<input id="view_as_enabled" type="checkbox"  name="pmprodev_options[view_as_enabled]" value="1" <?php checked( $pmprodev_options['view_as_enabled'], 1, true ); ?>>
	<label for="view_as_enabled"><?php _e( 'Check to enable the View As feature.', 'pmpro-toolkit' ); ?></label>
	<?php
}

/*
 * Display Page
 */
?>

<div class="wrap">
	<h2><?php esc_html_e( 'Developer\'s Toolkit for Paid Memberships Pro', 'pmpro-toolkit' ); ?></h2>
	<?php
		if ( isset( $_REQUEST[ 'page' ] ) ) {
			$view = sanitize_text_field( $_REQUEST[ 'page' ] );
		} else {
			$view = '';
		}
	?>
	<nav class="nav-tab-wrapper">
		<a href="<?php echo admin_url( 'options-general.php?page=pmprodev' );?>" class="nav-tab<?php if($view == 'pmprodev') { ?> nav-tab-active<?php } ?>"><?php esc_html_e('Toolkit Options', 'pmpro-toolkit' );?></a>
		<a href="<?php echo admin_url( 'tools.php?page=pmprodev-database-scripts' );?>" class="nav-tab<?php if($view == 'pmprodev-database-scripts') { ?> nav-tab-active<?php } ?>"><?php esc_html_e('Database Scripts', 'pmpro-toolkit' );?></a>
		<a href="<?php echo admin_url( 'tools.php?page=pmprodev-migration-assistant' );?>" class="nav-tab<?php if($view == 'pmprodev-migration-assistant') { ?> nav-tab-active<?php } ?>"><?php esc_html_e('Migration Assistant', 'pmpro-toolkit' );?></a>
	</nav>
	<form action="options.php" method="POST">
		<?php
		settings_fields( 'pmprodev_options' );
		do_settings_sections( 'pmprodev' );
		submit_button();
		?>
	</form>
</div>
