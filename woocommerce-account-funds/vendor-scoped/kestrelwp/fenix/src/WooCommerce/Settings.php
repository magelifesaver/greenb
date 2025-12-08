<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Settings\Setting_Adapter;
use WC_Admin_Settings;
/**
 * Settings handler for WooCommerce-specific settings.
 *
 * @since 1.7.1
 */
final class Settings
{
    use Is_Handler;
    /**
     * Constructor.
     *
     * @since 1.7.1
     *
     * @param Extension $plugin
     */
    protected function __construct(Extension $plugin)
    {
        self::$plugin = $plugin;
        self::add_action('woocommerce_admin_field_' . $plugin->hook('group_select'), [$this, 'output_group_select_field']);
        self::add_action('woocommerce_admin_field_' . $plugin->hook('group_multiselect'), [$this, 'output_group_select_field']);
    }
    /**
     * Outputs a group select field.
     *
     * @see Type::get_choices() this field will be output when the options are nested arrays and {@see Field::SELECT} is the current field type
     * @see Setting_Adapter::to_array()
     *
     * @since 1.7.1
     *
     * @param array<string, mixed> $value
     * @return void
     */
    protected function output_group_select_field(array $value): void
    {
        $option_value = $value['value'] ?? '';
        $field_description = WC_Admin_Settings::get_field_description($value);
        $description = $field_description['description'];
        $tooltip_html = $field_description['tooltip_html'];
        $custom_attributes = [];
        if (!empty($value['custom_attributes']) && is_array($value['custom_attributes'])) {
            foreach ($value['custom_attributes'] as $attribute => $attribute_value) {
                $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
            }
        }
        $option_groups = $value['options'] ?? [];
        ?>
		<tr class="<?php 
        echo esc_attr($value['row_class']);
        ?>">
			<th scope="row" class="titledesc">
				<label for="<?php 
        echo esc_attr($value['id']);
        ?>"><?php 
        echo esc_html($value['title']);
        ?> <?php 
        echo $tooltip_html;
        // phpcs:ignore 
        ?></label>
			</th>
			<td class="forminp forminp-<?php 
        echo esc_attr(sanitize_title($value['type']));
        ?>">
				<select
					name="<?php 
        echo esc_attr($value['field_name']);
        echo 'multiselect' === $value['type'] ? '[]' : '';
        ?>"
					id="<?php 
        echo esc_attr($value['id']);
        ?>"
					style="<?php 
        echo esc_attr($value['css']);
        ?>"
					class="<?php 
        echo esc_attr($value['class']);
        ?>"
					<?php 
        echo implode(' ', $custom_attributes);
        // phpcs:ignore 
        ?>
					<?php 
        echo 'multiselect' === $value['type'] ? 'multiple="multiple"' : '';
        ?>
				>
					<?php 
        foreach ($option_groups as $group_label => $options) {
            ?>
						<optgroup
							label="<?php 
            echo esc_attr($group_label);
            ?>"
						>
							<?php 
            foreach ($options as $option_key => $option_label) {
                ?>
								<option
									value="<?php 
                echo esc_attr($option_key);
                ?>"
									<?php 
                if (is_array($option_value)) {
                    selected(in_array((string) $option_key, $option_value, \true), \true);
                } else {
                    selected($option_value, (string) $option_key);
                }
                ?>
								><?php 
                echo esc_html($option_label);
                ?></option>
							<?php 
            }
            ?>
						</optgroup>
					<?php 
        }
        ?>
				</select> <?php 
        echo $description;
        // phpcs:ignore
        ?>
			</td>
		</tr>
		<?php 
    }
}
