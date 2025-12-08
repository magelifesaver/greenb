<?php
?>
<div id="barcodes-wrapper-tooltip">
    <div id="barcodes-tooltip"><?php echo esc_html__("Select orders and press this button to create barcodes.", "wpbcu-barcode-generator"); ?></div>
    <button type="button" name="barcodes_import_orders" id="barcodes-import-orders" class="button" data-action-type="orders">
        <!-- <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAABlBMVEX///8AAABVwtN+AAAAE0lEQVQI12NggIGobfiQkwpEFQAAfwsHv1O1owAAAABJRU5ErkJggg==" alt="" /> -->
        <span class="dashicons-before dashicons-tag"></span>
        <?php echo esc_html__("Order label", "wpbcu-barcode-generator") ?>
    </button>
</div>

<?php
?>
