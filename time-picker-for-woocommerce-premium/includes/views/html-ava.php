<?php
if (!defined('ABSPATH')) {
    exit;
}
?> <table class="wc-shipping-classes widefat">
	<thead>
		<tr> <?php foreach ($settings_columns as $class => $heading): ?> <th class="<?php echo esc_attr($class); ?>"><?php echo esc_html($heading); ?></th> <?php
endforeach; ?> </tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="<?php echo absint(count($settings_columns)); ?>"> <a class="button button-secondary wc-shipping-class-add" href="#"><?php esc_html_e('Add row', 'checkout-time-picker-for-woocommerce'); ?></a> <input type="hidden" class="insert" id="tpfwava" name="tpfw_ava_schedule" value=""> </td>
		</tr>
	</tfoot>
	<tbody class="wc-shipping-class-rows"></tbody>
</table>
<script type="text/html" id="tmpl-wc-shipping-class-row-blank">
	<tr>
		<td class="wc-shipping-classes-blank-state" colspan="<?php echo absint(count($settings_columns)); ?>">
			<p><?php esc_html_e('No schedules have been created.', 'checkout-time-picker-for-woocommerce'); ?></p>
		</td>
	</tr>
</script>
<script type="text/html" id="tmpl-wc-shipping-class-row">
	<tr data-id="{{ data.term_id }}"> <?php
foreach ($settings_columns as $class => $heading) {
    echo '<td class="' . esc_attr($class) . '">';
    switch ($class) {
        case 'wc-shipping-class-time_from':
?> <div class="edit"><input required type="time" name="time_from[{{ data.term_id }}]" data-attribute="time_from" value="{{ data.time_from }}" placeholder="<?php esc_attr_e('hh:mm', 'checkout-time-picker-for-woocommerce'); ?>" /></div> <?php
        break;
        case 'wc-shipping-class-time_to':
?> <div class="edit"><input required type="time" name="time_to[{{ data.term_id }}]" data-attribute="time_to" value="{{ data.time_to }}" placeholder="<?php esc_attr_e('hh:mm', 'checkout-time-picker-for-woocommerce'); ?>" /></div> <?php
        break;
        case 'wc-shipping-class-date':
?> <div class="edit"><input type="date" name="date[{{ data.term_id }}]" data-attribute="date" value="{{ data.date }}" /></div> <?php
        break;
        case 'wc-shipping-class-mode':
?> <div class="edit"> <select multiple class="wc-enhanced-select tpfw-multiple-select tpfw-ava-mode" name="mode[{{ data.term_id }}]" data-attribute="mode" value="{{ data.mode }}">
				<option value="pickup"><?php esc_html_e('Pickup', 'checkout-time-picker-for-woocommerce'); ?></option>
				<option value="del"><?php esc_html_e('Delivery', 'checkout-time-picker-for-woocommerce'); ?></option>
				<option  value="pickup_delivery"><?php esc_html_e('Pickup & Delivery', 'checkout-time-picker-for-woocommerce'); ?> </option> 
				
			</select> </div> <?php
        break;
       

        case 'wc-shipping-class-cats':
?> <select multiple class="wc-enhanced-select tpfw-multiple-select" name="cats[{{ data.term_id }}]" data-attribute="cats" value="{{ data.cats }}">
			<option value="tpfwallcategories"><?php esc_html_e('All Categories', 'checkout-time-picker-for-woocommerce'); ?></option>
		</select> <?php
        break;
        case 'wc-shipping-class-tags':
?> <select multiple class="wc-enhanced-select tpfw-multiple-select" name="tags[{{ data.term_id }}]" data-attribute="tags" value="{{ data.tags }}"> </select>
		<div class="row-actions"> <a href="#" class="wc-shipping-class-delete"><?php esc_html_e('Remove', 'checkout-time-picker-for-woocommerce'); ?></a> </div> <?php
        break;
        case 'wc-shipping-class-weekday':
?> <select multiple class="wc-enhanced-select tpfw-multiple-select" name="weekday[{{ data.term_id }}]" data-attribute="weekday" value="{{ data.weekday }}">
			<option value="1"><?php esc_html_e('Monday', 'checkout-time-picker-for-woocommerce'); ?></option>
			<option value="2"><?php esc_html_e('Tuesday', 'checkout-time-picker-for-woocommerce'); ?></option>
			<option value="3"><?php esc_html_e('Wednesday', 'checkout-time-picker-for-woocommerce'); ?></option>
			<option value="4"><?php esc_html_e('Thursday', 'checkout-time-picker-for-woocommerce'); ?></option>
			<option value="5"><?php esc_html_e('Friday', 'checkout-time-picker-for-woocommerce'); ?></option>
			<option value="6"><?php esc_html_e('Saturday', 'checkout-time-picker-for-woocommerce'); ?></option>
			<option value="0"><?php esc_html_e('Sunday', 'checkout-time-picker-for-woocommerce'); ?></option>
		</select> <?php
        break;
        default:
        break;
    }
    echo '</td>';
}
?>
	</tr>
</script>
