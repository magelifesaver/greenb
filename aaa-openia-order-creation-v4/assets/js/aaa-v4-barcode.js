jQuery(document).ready(($) => {
    const placeholderImage = window.AAA_V4_PLACEHOLDER_IMAGE || 'https://via.placeholder.com/60x60?text=Scan';

    let barcodeBuffer = '';
    let barcodeTimer;

    // ========== Barcode Scan Listener ==========
    $(document).on('keypress', (e) => {
        const char = String.fromCharCode(e.which);
        barcodeBuffer += char;

        if (barcodeTimer) clearTimeout(barcodeTimer);

        barcodeTimer = setTimeout(() => {
            const code = barcodeBuffer.trim();
            barcodeBuffer = '';

            if (code.length >= 6) {
                handleScannedBarcode(code);
            }
        }, 100);
    });

    const handleScannedBarcode = (barcode) => {
        $.post(ajaxurl, {
            action: 'aaa_v4_scan_lookup',
            barcode
        }, (response) => {
            if (response.success && response.data) {
                const p = response.data;
                const found = tryIncrementExisting(p.product_id);
                if (!found) {
                    insertProductRow(p.product_id, p.name, p.price, p.stock, p.image_url);
                }
                if (typeof recalculateEverything === 'function') recalculateEverything();
            } else {
                alert(`No match found for barcode: ${barcode}`);
            }
        });
    };

    const tryIncrementExisting = (product_id) => {
        let matched = false;

        $('#aaa-product-table tbody tr').each(function () {
            const $row = $(this);
            const rowVal = $row.find('input[name$="[product_id]"]').val().trim();
            const existingId = parseInt(rowVal);
            const targetId = parseInt(product_id);

            if (targetId && existingId === targetId) {
                const qtyField = $row.find('.product-qty-input');
                const currentQty = parseInt(qtyField.val()) || 0;
                const newQty = currentQty + 1;
                qtyField.val(newQty);

                const $note = $row.find('.product-note');
                const current = $note.text().trim();
                const parts = current.split(',').map(p => p.trim()).filter(Boolean);
                const scanned = `Scanned ×${newQty}`;
                const filtered = parts.filter(p => !p.startsWith('Scanned ×'));
                filtered.push(scanned);
                $note.text(filtered.join(', '));

                matched = true;
                return false; // break loop
            }
        });

        return matched;
    };

    const insertProductRow = (product_id, name, price, stock, image_url = '') => {
        const index = $('#aaa-product-table tbody tr').length;
        const safeImage = image_url || placeholderImage;

        const row = `
        <tr class="product-line">
            <td class="product-image">
                <img src="${safeImage}" style="max-width:60px; max-height:60px; cursor:pointer;">
            </td>
            <td><input type="text" class="product-name-input" name="products[${index}][product_name]" value="${name}" style="width:100%;"></td>
            <td><input type="number" class="product-qty-input" name="products[${index}][quantity]" value="1" style="width:60px;"></td>
            <td><input type="text" class="product-price-input" name="products[${index}][unit_price]" value="${price}" style="width:80px;" data-original="${price}"></td>
            <td><input type="text" class="product-special-input" name="products[${index}][special_price]" value="" style="width:80px;"></td>
            <td class="product-stock">${stock}</td>
            <td class="line-total">-</td>
            <td><input type="text" name="products[${index}][product_id]" value="${product_id}" style="width:80px;"></td>
            <td class="product-note">Scanned</td>
            <td>
                <button type="button" class="line-discount-percent button-modern">[%]</button>
                <button type="button" class="line-discount-fixed button-modern">[$]</button>
                <button type="button" class="remove-line-discount button-modern">X</button>
                <button type="button" class="remove-product button-modern">Remove</button>
            </td>
        </tr>`;

        $('#aaa-product-table tbody').append(row);
        if (typeof recalculateEverything === 'function') recalculateEverything();
    };
});