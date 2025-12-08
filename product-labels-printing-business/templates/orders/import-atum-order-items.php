<?php
?>

<div id="barcodes-wrapper-tooltip" style="float: initial;">
    <div id="barcodes-tooltip"><?php echo esc_html__("Select items and press this button to create barcodes.", "wpbcu-barcode-generator"); ?></div>
    <button type="button" name="barcodes_import_orders" id="barcodes-import-orders-items" class="button" data-action-type="atum-items" data-order-id="<?php echo esc_js($orderId); ?>">
        <!-- <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAABlBMVEX///8AAABVwtN+AAAAE0lEQVQI12NggIGobfiQkwpEFQAAfwsHv1O1owAAAABJRU5ErkJggg==" alt="" /> -->
        <span class="dashicons-before dashicons-tag"></span>
        <?php echo esc_html__("Create Product Labels", "wpbcu-barcode-generator") ?>
    </button>
</div>

<?php
?>
