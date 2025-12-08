<select name="order_product_category">
    <option value=""><?php echo esc_html__('All product categories', 'wpbcu-barcode-generator'); ?></option>

    <?php
    $current = isset($_GET['order_product_category']) ? $_GET['order_product_category'] : '';

    foreach ($values as $key => $label) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr($key),
            esc_attr($key == $current ? ' selected="selected"' : ''),
            esc_html($label)
        );
    }
    ?>
</select>
