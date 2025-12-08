<?php
use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;

$excluded = UserSettings::getOption('excludedProdStatuses', '');
$excluded = $excluded ? explode(",", $excluded) : array();
?>

<style>
    .barcodes-import-variation-item {
        float: none !important;
        margin: 4px 5px 0 !important;
        font-weight: 400;
    }

    .barcodes-import-variation-item img {
        position: relative;
        top: 4px;
    }
</style>
<button type="button"
    class="su-generator-button button barcodes-import-variation-item"
    title="Product Label"
    data-post-id="<?php echo esc_js($variation->ID); ?>"
    data-post-status="<?php echo esc_js($variation->post_status); ?>"
    data-is-excluded="<?php echo esc_js(in_array($variation->post_status, $excluded) ? 1 : 0); ?>"
    onclick="window.barcodesImportIdsType='variation'; window.barcodesImportIds=[<?php echo esc_js($variation->ID); ?>];">
    <span class="dashicons-before dashicons-tag"></span>
    <?php echo esc_html__("Product Label", "wpbcu-barcode-generator"); ?>
</button>

<?php
?>
