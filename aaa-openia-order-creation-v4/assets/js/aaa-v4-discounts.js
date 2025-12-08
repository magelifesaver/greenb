/**
 * File: assets/js/aaa-v4-discounts.js
 * Purpose: Handles cart & line discounts, coupon apply, and totals preview.
 */

jQuery(document).ready(($) => {
    const recalculateEverything = () => {
        if (typeof calculateAllTotals === 'function') calculateAllTotals();
        if (typeof updateCartSummary === 'function') updateCartSummary();
    };

    // === Utility: Ensure hidden fields exist ===
    const ensureDiscountFields = () => {
        const form = $('#aaa-v4-order-form');
        if (form.find('input[name="cart_discount_percent"]').length === 0) {
            form.append('<input type="hidden" name="cart_discount_percent" value="">');
        }
        if (form.find('input[name="cart_discount_fixed"]').length === 0) {
            form.append('<input type="hidden" name="cart_discount_fixed" value="">');
        }
        if (form.find('input[name="coupon_code"]').length === 0) {
            form.append('<input type="hidden" name="coupon_code" value="">');
        }
    };

    // === Sync hidden fields with active discount ===
    const setCartDiscount = (type, value) => {
        ensureDiscountFields();
        if (type === 'percent') {
            $('input[name="cart_discount_percent"]').val(value);
            $('input[name="cart_discount_fixed"]').val('');
        } else if (type === 'fixed') {
            $('input[name="cart_discount_percent"]').val('');
            $('input[name="cart_discount_fixed"]').val(value);
        }
    };

    // Export to global scope
    window.recalculateEverything = recalculateEverything;

    // ======= Preset % Cart Discount Buttons =======
    $('.discount-btn').on('click', function () {
        const percent = parseFloat($(this).data('discount'));
        if (!percent || percent <= 0) return;

        $('#aaa-cart-summary').attr('data-cart-discount', percent);
        $('#aaa-cart-summary').attr('data-cart-fixed', '');
        $('#aaa-cart-discount').val(percent);
        $('#aaa-cart-fixed').val('');
        recalculateEverything();
    });

    // ======= Custom % Cart Discount =======
    $('#custom-percent-btn').on('click', () => {
        const input = prompt("Enter cart % discount:");
        const percent = parseFloat(input);
        if (!percent || percent <= 0) return;

        $('#aaa-cart-summary').attr('data-cart-discount', percent);
        $('#aaa-cart-summary').attr('data-cart-fixed', '');
        $('#aaa-cart-discount').val(percent);
        $('#aaa-cart-fixed').val('');
        recalculateEverything();
    });

    // ======= Fixed $ Cart Discount =======
    $('#custom-fixed-btn').on('click', () => {
        const input = prompt("Enter fixed $ discount:");
        const fixed = parseFloat(input);
        if (!fixed || fixed <= 0) return;

        $('#aaa-cart-summary').attr('data-cart-discount', '');
        $('#aaa-cart-summary').attr('data-cart-fixed', fixed);
        $('#aaa-cart-discount').val('');
        $('#aaa-cart-fixed').val(fixed);
        recalculateEverything();
    });

    // ======= Target Total =======
    $('#target-total-btn').on('click', () => {
        const input = prompt("Enter target total:");
        const target = parseFloat(input);
        if (!target || target <= 0) return;

        const subtotal = parseFloat($('#aaa-subtotal').text()) || 0;
        if (target >= subtotal) {
            return alert(`Target must be less than subtotal (${subtotal.toFixed(2)}).`);
        }

        const percent = 100 - ((target / subtotal) * 100);
        $('#aaa-cart-summary').attr('data-cart-discount', percent.toFixed(2));
        $('#aaa-cart-summary').attr('data-cart-fixed', '');
        $('#aaa-cart-discount').val(percent.toFixed(2));
        $('#aaa-cart-fixed').val('');
        recalculateEverything();
    });

    // ======= Remove Cart Discounts =======
    $('#remove-cart-discount').on('click', () => {
        if (!confirm("Remove all cart-level discounts?")) return;
        $('#aaa-cart-summary').attr('data-cart-discount', '');
        $('#aaa-cart-summary').attr('data-cart-fixed', '');
        $('#aaa-cart-summary').removeAttr('data-coupon-code data-coupon-type data-coupon-amount');
        $('#aaa-cart-discount').val('');
        $('#aaa-cart-fixed').val('');
        $('input[name="coupon_code"]').val('');
        $('#aaa-v4-coupon-message').html('');
        recalculateEverything();
    });

    // ======= Per-Line Discounts =======
    $(document).on('click', '.line-discount-percent', function () {
        const $row = $(this).closest('tr');
        const input = prompt("Line % off:");
        const percent = parseFloat(input);
        if (!percent || percent <= 0) return;

        const basePrice = parseFloat($row.find('.product-price-input').data('original')) || 0;
        const newPrice = basePrice * (1 - percent / 100);
        $row.find('.product-special-input').val(newPrice.toFixed(2));
        appendNote($row, `-${percent}%`);
        recalculateEverything();
    });

    $(document).on('click', '.line-discount-fixed', function () {
        const $row = $(this).closest('tr');
        const input = prompt("Line $ off:");
        const fixed = parseFloat(input);
        if (!fixed || fixed <= 0) return;

        const basePrice = parseFloat($row.find('.product-price-input').data('original')) || 0;
        if (fixed >= basePrice) return alert("Discount exceeds price.");
        const newPrice = basePrice - fixed;
        $row.find('.product-special-input').val(newPrice.toFixed(2));
        appendNote($row, `-$${fixed}`);
        recalculateEverything();
    });

    $(document).on('click', '.remove-line-discount', function () {
        const $row = $(this).closest('tr');
        $row.find('.product-special-input').val('');
        removeNote($row, /^[-\\$%]/);
        recalculateEverything();
    });

// ───── Coupon Apply Button ─────
$('#aaa-v4-apply-coupon').on('click', function () {
    const coupon = $('#aaa-v4-coupon-code').val();
    if (!coupon) {
        $('#aaa-v4-coupon-message').html('<span style="color:red;">Please select a coupon.</span>');
        return;
    }

    // Customer info
    const customerId    = $('input[name="customer_id"]').val() || 0;
    const customerEmail = $('input[name="customer_email"]').val() || '';

    // Build products array from preview table
    const products = [];
    $('#aaa-product-table tbody tr').each(function () {
        const $row = $(this);
        const product_id    = $row.find('input[name*="[product_id]"]').val();
        const quantity      = $row.find('.product-qty-input').val();
        const unit_price    = $row.find('.product-price-input').val();
        const special_price = $row.find('.product-special-input').val();
        if (product_id) {
            products.push({
                product_id: product_id,
                quantity: quantity,
                unit_price: unit_price,
                special_price: special_price
            });
        }
    });

    $.post(ajaxurl, {
        action: 'aaa_v4_apply_coupon',
        coupon: coupon,
        customer_id: customerId,
        customer_email: customerEmail,
        products: products
    }, function (response) {
        if (response.success) {
            $('#aaa-v4-coupon-message').html(
                `<span style="color:green;">Applied: ${response.data.code} (${response.data.type}, ${response.data.amount})</span>`
            );
            $('input[name="coupon_code"]').val(response.data.code);

            $('#aaa-cart-summary')
                .attr('data-coupon-code', response.data.code)
                .attr('data-coupon-type', response.data.type)
                .attr('data-coupon-amount', response.data.amount);

            recalculateEverything();
        } else {
            // Show both user-friendly message and technical error code (if provided)
            let debugMsg = '';
            if (response.data.code) {
                debugMsg = `<br><small style="color:#666;">[Debug code: ${response.data.code}]</small>`;
            }
            $('#aaa-v4-coupon-message').html(
                `<span style="color:red;">${response.data.message}</span>${debugMsg}`
            );

            $('#aaa-cart-summary')
                .removeAttr('data-coupon-code')
                .removeAttr('data-coupon-type')
                .removeAttr('data-coupon-amount');
        }
    });
});

    // ======= Utility: Append/Remove Notes =======
    const appendNote = ($row, text) => {
        const $note = $row.find('.product-note');
        const current = $note.text().trim();
        const parts = current.split(',').map(p => p.trim()).filter(Boolean);
        if (!parts.includes(text)) parts.push(text);
        $note.text(parts.join(', '));
    };

    const removeNote = ($row, matchPattern) => {
        const $note = $row.find('.product-note');
        const parts = $note.text().split(',').map(s => s.trim()).filter(Boolean);
        const filtered = parts.filter(p => !p.match(matchPattern));
        $note.text(filtered.join(', '));
    };

    // ======= Cart Summary Rendering =======
    const updateCartSummary = () => {
        let subtotal = 0;
        let totalQty = 0;

        $('#aaa-product-table tbody tr').each(function () {
            const $row = $(this);
            const qty = parseFloat($row.find('.product-qty-input').val()) || 0;
            const special = parseFloat($row.find('.product-special-input').val());
            const unitInput = $row.find('.product-price-input');
            const basePrice = parseFloat(unitInput.data('original')) || parseFloat(unitInput.val()) || 0;
            const price = !isNaN(special) && special > 0 ? special : basePrice;
            const lineTotal = price * qty;

            subtotal += lineTotal;
            totalQty += qty;

            $row.find('.line-total').text(`$${lineTotal.toFixed(2)}`);
        });

        const discountPercent = parseFloat($('#aaa-cart-summary').attr('data-cart-discount')) || 0;
        const discountFixed   = parseFloat($('#aaa-cart-summary').attr('data-cart-fixed')) || 0;
        let discount = 0;
        if (discountPercent > 0) {
            discount = (subtotal * discountPercent / 100);
        } else if (discountFixed > 0) {
            discount = discountFixed;
        }

        // Coupon Handling
        let couponValue = 0;
        const couponType   = $('#aaa-cart-summary').attr('data-coupon-type');
        const couponAmount = parseFloat($('#aaa-cart-summary').attr('data-coupon-amount')) || 0;

        if (couponType && couponAmount > 0) {
            if (couponType.includes('percent')) {
                couponValue = subtotal * (couponAmount / 100);
            } else {
                couponValue = couponAmount;
            }
        }

        const finalTotal = subtotal - discount - couponValue;

        $('#aaa-total-items').text(totalQty);
        $('#aaa-subtotal').text(subtotal.toFixed(2));
        $('#aaa-discount-amount').text((discount + couponValue).toFixed(2));
        $('#aaa-total').text(finalTotal.toFixed(2));
    };

    // ======= Live Recalc =======
    $(document).on('input', '.product-qty-input, .product-special-input, .product-price-input', () => {
        recalculateEverything();
    });

    // ======= Init =======
    ensureDiscountFields();
    recalculateEverything();
});
