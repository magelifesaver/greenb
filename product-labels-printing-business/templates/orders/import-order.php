<?php
?>

<style>
    #barcode-print-order-create .inside {
        text-align: center !important;
    }

    #barcode-print-order-create .inside table {
        display: block !important;
        overflow: hidden !important;
        width: 0 !important;
        height: 0 !important;
    }
</style>

<table class="wp-list-table" style="position: absolute;">
    <tbody>
        <tr>
            <th id="cb" class="manage-column column-cb check-column">
                <input type="checkbox" name="post[]" checked="checked" value=" <?php echo esc_js($orderId); ?>">
            </th>
        </tr>
    </tbody>
</table>

<button type="button" name="barcodes_import_orders" id="barcodes-import-orders" class="button" data-action-type="orders">
    <!-- <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAABlBMVEX///8AAABVwtN+AAAAE0lEQVQI12NggIGobfiQkwpEFQAAfwsHv1O1owAAAABJRU5ErkJggg==" alt="" /> -->
    <span class="dashicons-before dashicons-tag"></span>
    <?php echo esc_html__("Order label", "wpbcu-barcode-generator") ?>
</button>
<?php
?>
