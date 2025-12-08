<?php
?>

<div id="barcodes-wrapper-tooltip">
    <div id="barcodes-tooltip"><?php echo esc_html__("Select coupons and press this button to create barcodes.", "wpbcu-barcode-generator"); ?></div>
    <button type="button" name="barcodes_import_wc_coupons" id="barcodes-import-wc-coupons" class="button" data-action-type="wc-coupons">
        <!-- <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAABlBMVEX///8AAABVwtN+AAAAE0lEQVQI12NggIGobfiQkwpEFQAAfwsHv1O1owAAAABJRU5ErkJggg==" alt="" /> -->
        <span class="dashicons-before dashicons-tag"></span>
        <?php echo esc_html__("Create Label", "wpbcu-barcode-generator") ?>
    </button>
</div>

<?php
?>
