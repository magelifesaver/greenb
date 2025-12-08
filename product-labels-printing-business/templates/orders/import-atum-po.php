<?php
?>

<style>
    #barcode-print-atum-po-create .inside {
        text-align: center !important;
    }

    #barcode-print-atum-po-create .inside table {
        display: block !important;
        overflow: hidden !important;
        width: 0 !important;
        height: 0 !important;
    }
</style>

<table class="wp-list-table" style="margin-top: 1em;">
    <tbody>
        <tr>
            <th id="cb" class="manage-column column-cb check-column">
                <input type="checkbox" name="post[]" checked="checked" value=" <?php echo esc_js($orderId); ?>">
            </th>
        </tr>
    </tbody>
</table>

<button type="button" name="barcodes_import_orders" id="barcodes-import-orders" class="button" data-action-type="atum-po">
    <span class="dashicons-before dashicons-tag"></span>
    <?php echo esc_html__("Create Order Labels", "wpbcu-barcode-generator") ?>
</button>
<?php
?>
