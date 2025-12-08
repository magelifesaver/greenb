<?php
use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;

$excluded = UserSettings::getOption('excludedProdStatuses', '');
$excluded = $excluded ? explode(",", $excluded) : array();
?>

<!-- su-generator-button button barcodes-import-single-product -->
<?php 
?>
<?php if ($product) : ?>
    <?php
    $dataVariations = $product && $product->get_type() === "variable" ? 1 : 0;
    $dataIsExcluded = in_array($post->post_status, $excluded) ? 1 : 0;
    ?>
    <button type="button"
        class="su-generator-button button barcodes-import-single-product-label"
        data-is-excluded="<?php echo esc_attr($dataIsExcluded) ?>"
        data-post-status="<?php echo esc_attr($post->post_status) ?>"
        data-post-id="<?php echo esc_attr($post->ID) ?>"
        data-action-type="products"
        data-variations="<?php echo esc_attr($dataVariations) ?>"
        data-is-print="0"
        onclick="window.barcodesImportIdsType='simple'; window.barcodesImportIds=[<?php echo esc_attr($post->ID) ?>];"
        data-post-id="<?php echo esc_js($post->ID); ?>"
        title="Product Label"><span class="dashicons-before dashicons-tag"></span></button>
<?php else : ?>
    <button type="button"
        class="su-generator-button button barcodes-product-print-button"
        data-post-id="<?php echo esc_js($post->ID); ?>"
        title="Product Label"><span class="dashicons-before dashicons-tag"></span></button>
<?php endif; ?>

<?php
?>
