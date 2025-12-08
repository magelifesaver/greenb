<?php
if (!defined('ABSPATH')) {
    exit;
}
?> <table id="tpfw_dynamic_preperation_table" class="wc-shipping-classes widefat ">
	<thead>
		<tr> <?php foreach ($settings_columns as $class => $heading): ?> <th class="<?php echo esc_attr($class); ?>"><?php echo esc_html($heading); ?></th> <?php
endforeach; ?> </tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="<?php echo absint(count($settings_columns)); ?>"> <a class="button button-secondary wc-shipping-class-add" href="#"><?php esc_html_e('Add row', 'checkout-time-picker-for-woocommerce'); ?></a> <input type="hidden" class="insert" id="tpfwfixedordertimes" name="tpfw_ordertime_per_cats" value=""> </td>
		</tr>
	</tfoot>
	<tbody class="wc-shipping-class-rows"></tbody>
</table>
<script type="text/html" id="tmpl-wc-shipping-class-row-blank">
	<tr>
		<td class="wc-shipping-classes-blank-state" colspan="<?php echo absint(count($settings_columns)); ?>">
			<p><?php esc_html_e('No rules have been created.', 'checkout-time-picker-for-woocommerce'); ?></p>
		</td>
	</tr>
</script>
<script type="text/html" id="tmpl-wc-shipping-class-row">
	<tr data-id="{{ data.term_id }}"> <?php
foreach ($settings_columns as $class => $heading) {
    echo '<td class="' . esc_attr($class) . '">';
    switch ($class) {
        case 'wc-shipping-class-cats':
?> <select multiple class="wc-enhanced-select tpfw-multiple-select" name="cats[{{ data.term_id }}]" data-attribute="cats" value="{{ data.cats }}">
			<option value="tpfwallcategories"><?php esc_html_e('All Categories', 'checkout-time-picker-for-woocommerce'); ?></option>
		</select> <?php
        break;
        case 'wc-shipping-class-rate':
?> <div class="edit"><input type="number" name="rate[{{ data.term_id }}]" data-attribute="rate" value="{{ data.rate }}" placeholder="<?php esc_attr_e('minutes', 'checkout-time-picker-for-woocommerce'); ?>" /></div>
		<div class="row-actions"> <a href="#" class="wc-shipping-class-delete"><?php esc_html_e('Remove', 'checkout-time-picker-for-woocommerce'); ?></a> </div> <?php
        break;
    }
    echo '</td>';
}
?>
	</tr>
</script>

