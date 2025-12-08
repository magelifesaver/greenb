<?php defined('ABSPATH') or exit ?>

<?php
    $filter_row_key = isset($filter_row_count)? $filter_row_count: "{i}";
    $filter_method = isset($filter->method)? $filter->method: "include";
    $filter_value = isset($filter->value)? $filter->value: "";
?>

<?php if (!isset($filter_row_count)) { ?>
<div class="filter_collections wdr-condition-type-options">
    <?php } ?>
    <div class="filter_collections_group products_group wdr-products_group">
        <div class="wdr-product_filter_method">
            <select name="filters[<?php echo esc_attr($filter_row_key); ?>][method]">
                <option value="include" <?php if ($filter_method == 'include') echo 'selected'; ?>><?php esc_html_e('Include', 'wdr-collections'); ?></option>
                <option value="exclude" <?php if ($filter_method == 'exclude') echo 'selected'; ?>><?php esc_html_e('Exclude', 'wdr-collections'); ?></option>
            </select>
        </div>
        <div class="awdr-product-selector">
            <select
                    class="<?php echo isset($filter_row_count)? 'wdr_col_load_select2' : 'wdr_col_select2'; ?> awdr_validation"
                    data-list="filter_collections"
                    data-placeholder="<?php esc_attr_e('Select Collection', 'wdr-collections');?>"
                    name="filters[<?php echo esc_attr($filter_row_key); ?>][value][]">
                <?php
                if (!empty($filter_value)) {
                    $collections = \WDR_COL\App\Models\Collections::get((array) $filter->value);
                    foreach ($collections as $collection) {
                        echo '<option value="' . esc_attr($collection->id) . '" selected>' . esc_html('#' . $collection->id . ' ' . $collection->title) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
    </div>
<?php if (!isset($filter_row_count)) { ?>
</div>
<?php } ?>