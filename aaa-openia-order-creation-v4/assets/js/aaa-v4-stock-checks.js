jQuery(document).ready(($) => {
    const stockMap = new Map();

    // ========== Track and Validate Qty vs Stock ==========
    $(document).on('input', '.product-qty-input', function () {
        const $row = $(this).closest('tr');
        const qty = parseInt($(this).val()) || 0;
        const stock = parseInt($row.find('.product-stock').text()) || 0;

        if (qty > stock && stock > 0) {
            if (!confirm(`Only ${stock} in stock. Proceed anyway?`)) {
                $(this).val(stock);
                $row.removeAttr('data-overstock');
                $(this).css('border', '');
            } else {
                $row.attr('data-overstock', 'true');
                $(this).css('border', '2px solid red');
            }
        } else {
            $row.removeAttr('data-overstock');
            $(this).css('border', '');
        }
    });

    // ========== Confirm if Order Will Cause Out of Stock ==========
    window.confirmOutOfStock = () => {
        const warnings = [];

        $('#aaa-product-table tbody tr').each(function () {
            const $row = $(this);
            const name = $row.find('.product-name-input').val();
            const qty = parseInt($row.find('.product-qty-input').val()) || 0;
            const stock = parseInt($row.find('.product-stock').text()) || 0;

            if (qty === stock && stock > 0) {
                warnings.push(name + ` (will go out of stock)`);
            }
        });

        if (warnings.length > 0) {
            return confirm("This order will cause the following product(s) to go out of stock:\n\n" + warnings.join('\n') + "\n\nContinue?");
        }

        return true; // no warnings
    };

    // ========== Click Image to View Full Size ==========
    $(document).on('click', '.product-image img', function () {
        const src = $(this).attr('src');
        if (!src) return;

        const modal = $(`
            <div class="aaa-img-modal-overlay">
                <div class="aaa-img-modal-content">
                    <img src="${src}" style="max-width:90%; max-height:90%;">
                </div>
            </div>
        `).css({
            position: 'fixed',
            top: 0, left: 0,
            width: '100%', height: '100%',
            background: 'rgba(0,0,0,0.85)',
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
            zIndex: 10000
        });

        modal.on('click', () => modal.remove());
        $('body').append(modal);
    });
});
