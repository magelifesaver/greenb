<?php
/**
 * Region switcher tools
 *
 * @since 0.5.1
 *
 * @var array $field_atts
 */

defined( 'ABSPATH' ) || die;

?>

<span class="checkbox-wrapper">
	<input id="remove-datepicker-range" type="checkbox" checked="checked" class="remove-datepicker-range"/> <label for="remove-datepicker-range"><?php esc_attr_e( 'Select a date range', ATUM_LOGS_TEXT_DOMAIN ); ?></label>
</span>

<div class="tool-fields-wrapper range-fields-block">

	<div class="repeatable-row">
		<div class="tool-fields-from">
			<input name="<?php echo esc_attr( $field_atts['id'] ) ?>_from" data-tool="<?php echo esc_attr( $field_atts['id'] ) ?>" type="text" class="range-from range-datepicker" placeholder="<?php esc_attr_e( 'From...', ATUM_LOGS_TEXT_DOMAIN ) ?>"/>
		</div>

		<div class="tool-fields-to">
			<input name="<?php echo esc_attr( $field_atts['id'] ) ?>_to" data-tool="<?php echo esc_attr( $field_atts['id'] ) ?>" type="text" class="range-to range-datepicker" placeholder="<?php esc_attr_e( 'To...', ATUM_LOGS_TEXT_DOMAIN ) ?>"/>
		</div>

	</div>

	<input type="hidden" id="<?php echo esc_attr( $field_atts['id'] ) ?>" class="range-value" value="">
</div>
